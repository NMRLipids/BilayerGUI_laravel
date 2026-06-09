<?php

use App\Mcp\Servers\FairmdLipidsServer;
use App\Mcp\Tools\AdvancedTrajectorySearchTool;
use App\Mcp\Tools\BestSimulationsTool;
use App\Mcp\Tools\DatabaseStatisticsTool;
use App\Mcp\Tools\GetLipidTool;
use App\Mcp\Tools\GetTrajectoryTool;
use App\Mcp\Tools\SearchDatabaseTool;

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
