<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\SitemapXmlController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LipidController;
use App\Http\Controllers\ExperimentController;
use Illuminate\Support\Facades\View;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $lipids = Cache::remember('welcome.lipid_list', now()->addHours(12), function () {
        return \App\Lipido::orderBy('molecule')->get()->unique('molecule')->values();
    });
    return view('welcome', compact('lipids'));
})->name('welcome');

Route::redirect('/home', '/')->name('home');
Route::redirect('/about', '/#about')->name('about');

// advanced search
Route::get('/advanced-search', 'App\Http\Controllers\NewAdvancedSearchController@form')->name('new_advanced_search.form');
Route::get('/advanced-search/result', 'App\Http\Controllers\NewAdvancedSearchController@results')->name('new_advanced_search.results');


Route::get('/advanced-search/exportcompare', 'App\Http\Controllers\NewAdvancedSearchController@exportarcompare')->name('new_advanced_search.exportarcompare');
Route::redirect('/new-advanced-search/', '/advanced-search/')->name('new_advanced_search.redirect');
// ------
// Statistics
Route::get('/statistics', 'App\Http\Controllers\StatisticsController@results')->name('statistics.results');
Route::get('/totals', 'App\Http\Controllers\StatisticsController@totals')->name('statistics.totals');
// File: removed


Route::get('/filtro/{codigo}', 'App\Http\Controllers\FiltrosController@html')->name('filtros.html');
Route::get('/filtro-busqueda-avanzada/{codigo}/{numero}', 'App\Http\Controllers\FiltrosController@htmlBusquedaAvanzada')->name('filtros.html_busqueda_avanzada');

# Routes for trajectories and simulations, both URLs point to the same controller method
Route::get('/trajectories/{trayectoria_id}', 'App\Http\Controllers\TrayectoriasController@show')
->where('trayectoria_id', '[0-9]+')
->name('trayectorias.show');
Route::get('/simulations/{trayectoria_id}', 'App\Http\Controllers\TrayectoriasController@show')
->where('trayectoria_id', '[0-9]+')
->name('simulations.show');

Route::get('/trajectories', 'App\Http\Controllers\TrayectoriasController@list')->name('trayectorias.list');
Route::get('/simulations', 'App\Http\Controllers\TrayectoriasController@list')->name('simulations.list');


Route::get('/filtro-busqueda-avanzada-selects/{codigo}/{numero}', 'App\Http\Controllers\FiltrosController@htmlBusquedaAvanzadaSelects')->name('filtros.html_busqueda_avanzada_selects');


Route::get('/search', 'App\Http\Controllers\SearchController@results')->name('search.results');

// AUTOCOMPLETE
Route::get('/search/basic', 'App\Http\Controllers\SearchController@basic')->name('search.basic');


Route::get('/sitemap.xml', [SitemapXmlController::class, 'sitemap']);

Route::get('/robots.txt', function () {
    if (app()->environment('production')) {
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . 'Sitemap: ' . url('/sitemap.xml') . "\n";
    } else {
        $body = "User-agent: *\nDisallow: /\n";
    }

    return response($body, 200)
        ->header('Content-Type', 'text/plain; charset=utf-8');
})->name('robots.txt');

// Routes for advanced search autocomplete fields
Route::get('lipids/autocomplete', function (Illuminate\Http\Request  $request) {
    $term = $request->term ?: ''; //  <- esto depende del js que lo manda asi
    $tags = App\Lipido::where('molecule', 'LIKE', '%' . $term . '%')
        ->orderBy('molecule', 'asc')
        ->pluck('molecule', 'id', 'name', 'mapping')
        ->toArray();
    $valid_tags = [];
    foreach ($tags as $id => $tag) {
        $valid_tags[] = ['id' => $id, 'molecule' => $tag];
    }
    return $valid_tags;
})->name('lipids.autocomplete');


/* Implementing a route for lipids
/
*/
// Route::get('/lipid/{lipid_id}', 'LipidosController@show')->name('lipid.show');
// Temporary route for lipid details using a closure with dummy data
// In a real application, this should be replaced with a proper controller method
// that fetches lipid details from the database.
// Lipid_id can be either the numeric ID or the short_name

Route::get('/lipid/{lipid_id}', [LipidController::class, 'show']
)->name('lipid.show');


Route::get('/lipids', [LipidController::class, 'list'])
    ->name('lipids.list');

Route::get('/experiment/{type}/{path}', [ExperimentController::class, 'show'])
    ->where(['type' => 'FF|OP', 'path' => '.+'])
    ->name('experiments.show');

Route::get('/experiments', [ExperimentController::class, 'list'])
    ->name('experiments.list');    

// MCP discovery advertisements. Both expose the public MCP endpoint so that
// MCP-aware clients and LLM crawlers can find it. The endpoint URL is built from
// the current request host so it is correct in any deployment.
Route::get('/llms.txt', function () {
    $mcpUrl = url('/mcp/fairmd-lipids');
    $site = url('/');
    $sitemap = url('/sitemap.xml');
    $name = config('app.name', 'FAIRMD Lipids');

    $body = <<<TXT
    # {$name}

    > {$name} is a databank for visualization of molecular dynamics (MD) simulations
    > of lipid membranes and related NMR/X-ray experiments. It exposes a read-only
    > Model Context Protocol (MCP) server for programmatic, AI-assistant access.

    ## MCP

    - MCP endpoint (Streamable HTTP): {$mcpUrl}
    - Protocol: Model Context Protocol (https://modelcontextprotocol.io)
    - Access: read-only, rate-limited

    ## Site

    - Home: {$site}
    - Sitemap: {$sitemap}
    TXT;

    return response($body, 200)
        ->header('Content-Type', 'text/plain; charset=utf-8');
})->name('llms.txt');

Route::get('/.well-known/mcp', function () {
    $name = config('app.name', 'FAIRMD Lipids');

    return response()->json([
        'name' => $name,
        'description' => 'Read-only MCP access to the '.$name.' databank of lipid membrane MD simulations and related NMR/X-ray experiments.',
        'documentation' => 'https://github.com/NMRLipids/BilayerUI_laravel/blob/main/app/Mcp/README.md',
        'servers' => [
            [
                'name' => 'fairmd-lipids',
                'transport' => 'streamable-http',
                'url' => url('/mcp/fairmd-lipids'),
            ],
        ],
    ], 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
})->name('well-known.mcp');

