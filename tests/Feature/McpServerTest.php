<?php

use App\Mcp\Servers\FairmdLipidsServer;
use App\Mcp\Tools\AdvancedTrajectorySearchTool;
use App\Mcp\Tools\BestSimulationsTool;
use App\Mcp\Tools\DatabaseStatisticsTool;
use App\Mcp\Tools\GetLipidTool;
use App\Mcp\Tools\GetTrajectoryTool;
use App\Mcp\Tools\SearchDatabaseTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

test('search-database finds POPC lipids', function () {
    $response = FairmdLipidsServer::tool(SearchDatabaseTool::class, [
        'text' => 'POPC',
    ]);

    $response->assertOk()->assertSee('POPC');
});

test('search-database requires text', function () {
    $response = FairmdLipidsServer::tool(SearchDatabaseTool::class, []);

    $response->assertHasErrors();
});

test('best-simulations ranks POPC simulations best-first', function () {
    $response = FairmdLipidsServer::tool(BestSimulationsTool::class, [
        'lipid' => 'POPC',
        'limit' => 5,
    ]);

    $response->assertOk()->assertSee('rank-product');
});

test('get-trajectory returns detail for a known trajectory', function () {
    $response = FairmdLipidsServer::tool(GetTrajectoryTool::class, [
        'id' => 805,
    ]);

    $response->assertOk()->assertSee('805');
});

test('get-trajectory errors for a missing trajectory', function () {
    $response = FairmdLipidsServer::tool(GetTrajectoryTool::class, [
        'id' => 999999,
    ]);

    $response->assertHasErrors();
});

test('get-lipid returns detail for POPC', function () {
    $response = FairmdLipidsServer::tool(GetLipidTool::class, [
        'id_or_molecule' => 'POPC',
    ]);

    $response->assertOk()->assertSee('POPC');
});

test('advanced-trajectory-search filters by lipid', function () {
    $response = FairmdLipidsServer::tool(AdvancedTrajectorySearchTool::class, [
        'lipids' => ['POPC'],
        'lipids_operator' => ['and'],
        'per_page' => 5,
    ]);

    $response->assertOk()->assertSee('total');
});

test('database-statistics returns totals', function () {
    $response = FairmdLipidsServer::tool(DatabaseStatisticsTool::class, []);

    $response->assertOk()->assertSee('total_trajectories');
});

test('advanced-trajectory-search applies a lone minimum bound and excludes null rows', function () {
    // Only trajectory 805 has op_quality_total set; 0 must be a valid bound.
    $response = FairmdLipidsServer::tool(AdvancedTrajectorySearchTool::class, [
        'quality_total_min' => 0,
        'per_page' => 100,
    ]);

    $response->assertOk()->assertStructuredContent(function (AssertableJson $json) {
        $json->where('total', 1)->where('results.0.id', 805)->etc();
    });
});

test('advanced-trajectory-search applies a lone maximum bound', function () {
    // Temperatures in the test data: 300, 310, 310, 323.
    $response = FairmdLipidsServer::tool(AdvancedTrajectorySearchTool::class, [
        'temperature_max' => 310,
        'per_page' => 100,
    ]);

    $response->assertOk()->assertStructuredContent(function (AssertableJson $json) {
        $json->where('total', 3)->etc();
    });
});

test('advanced-trajectory-search keeps range filters with multiple force-field patterns', function () {
    // Lipid17 (183) has no OP quality score, OPLS3e (805) has one; the OR over
    // force-field patterns must not swallow the quality filter.
    $response = FairmdLipidsServer::tool(AdvancedTrajectorySearchTool::class, [
        'force_fields' => ['Lipid17', 'OPLS3e'],
        'force_fields_operator' => ['contains', 'contains'],
        'quality_total_min' => 0,
        'quality_total_max' => 2,
        'per_page' => 100,
    ]);

    $response->assertOk()->assertStructuredContent(function (AssertableJson $json) {
        $json->where('total', 1)->where('results.0.id', 805)->etc();
    });
});

test('get-lipid returns each property once even when linked property rows duplicate values', function () {
    $lipidId = DB::table('lipids')->where('molecule', 'POPC')->value('id');
    $property = DB::table('lipid_properties')
        ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
        ->where('lipid_id', $lipidId)
        ->where('name', '!=', 'description')
        ->first();

    $expectedCount = DB::table('lipid_properties')
        ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
        ->where('lipid_id', $lipidId)
        ->where('name', '!=', 'description')
        ->count();

    // Link a second property row with byte-identical name/value/unit.
    $duplicateId = DB::table('properties')->insertGetId([
        'name' => $property->name,
        'value' => $property->value,
        'unit' => $property->unit,
        'type' => $property->type,
    ]);
    DB::table('lipid_properties')->insert(['lipid_id' => $lipidId, 'property_id' => $duplicateId]);

    $response = FairmdLipidsServer::tool(GetLipidTool::class, [
        'id_or_molecule' => 'POPC',
    ]);

    $response->assertOk()->assertStructuredContent(function (AssertableJson $json) use ($expectedCount) {
        $json->count('properties', $expectedCount)->etc();
    });
});

test('best-simulations reports ranked_count for the scored subset', function () {
    $response = FairmdLipidsServer::tool(BestSimulationsTool::class, [
        'limit' => 100,
    ]);

    $response->assertOk()->assertSee('ranked_count');
});
