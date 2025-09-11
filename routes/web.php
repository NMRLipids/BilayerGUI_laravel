<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\SitemapXmlController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LipidController;
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
    return view('welcome');
})->name('welcome');

Route::get('/home', 'App\Http\Controllers\HomeController@index')->name('home');

// Authentication Routes...
// These routes are commented to disable user authentication
//Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
//Route::post('/login', 'Auth\LoginController@login');
//Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

// this route didn't work
// ICICIC: it is unclear what two routes were meant here. Commenting out for now.
// Route::get('/advanced-search', 'App\Http\Controllers\BusquedaAvanzadaController@form')->name('advanced-search.form');
// Route::get('/advanced-search/result', 'App\Http\Controllers\BusquedaAvanzadaController@results')->name('advanced-search.results');
// Route::post('/advanced-search/export', 'App\Http\Controllers\BusquedaAvanzadaController@export')->name('advanced-search.export');

// TEST advanced search
Route::get('/new-advanced-search', 'App\Http\Controllers\NewAdvancedSearchController@form')->name('new_advanced_search.form');
Route::get('/new-advanced-search/result', 'App\Http\Controllers\NewAdvancedSearchController@results')->name('new_advanced_search.results');
Route::get('/new-advanced-search/compare', 'App\Http\Controllers\NewAdvancedSearchController@compare')->name('new_advanced_search.compare');
Route::post('/new-advanced-search/updatecompare', 'App\Http\Controllers\NewAdvancedSearchController@updatecompare')->name('new_advanced_search.updatecompare');
Route::get('/new-advanced-search/export', 'App\Http\Controllers\NewAdvancedSearchController@resultsExport')->name('new_advanced_search.resultsExportacion');

Route::get('/new-advanced-search/exportcompare', 'App\Http\Controllers\NewAdvancedSearchController@exportarcompare')->name('new_advanced_search.exportarcompare');
// ------
// Statistics
Route::get('/statistics', 'App\Http\Controllers\StatisticsController@results')->name('statistics.results');
Route::get('/totals', 'App\Http\Controllers\StatisticsController@totals')->name('statistics.totals');
// File
Route::get('files/{id}/{file}', 'App\Http\Controllers\FileController@download')->name('download');
Route::get('filesp/{id}/{file}', 'App\Http\Controllers\FileController@downloadp')->name('downloadp');
Route::get('filesff/{id}/{file}', 'App\Http\Controllers\FileController@downloadff')->name('downloadff');

Route::get('/filtro/{codigo}', 'App\Http\Controllers\FiltrosController@html')->name('filtros.html');
Route::get('/filtro-busqueda-avanzada/{codigo}/{numero}', 'App\Http\Controllers\FiltrosController@htmlBusquedaAvanzada')->name('filtros.html_busqueda_avanzada');
Route::get('/trajectories/{trayectoria_id}', 'App\Http\Controllers\TrayectoriasController@show')->name('trayectorias.show');

Route::get('/filtro-busqueda-avanzada-selects/{codigo}/{numero}', 'App\Http\Controllers\FiltrosController@htmlBusquedaAvanzadaSelects')->name('filtros.html_busqueda_avanzada_selects');


Route::get('/search', 'App\Http\Controllers\SearchController@results')->name('search.results');

// AUTOCOMPLETE
Route::get('/search/basic', 'App\Http\Controllers\SearchController@basic')->name('search.basic');


// -- Image routes: These routes do not work
// ICICIC: Also these routes prevented caching of the routes they are assigned the same name as other routes
// Please clarify their purpose before re-enabling
// Route::get('convert-pdf-to-image', [ImageController::class, 'index'])->name('form');
// Route::get('OptimizeImages', [ImageController::class, 'index'])->name('form');
// ---


Route::get('/sitemap.xml', [SitemapXmlController::class, 'sitemap']);

// Routes for advanced search autocomplete fields
Route::get('listLipidos', function (Illuminate\Http\Request  $request) {
    $term = $request->term ?: ''; //  <- esto depende del js que lo manda asi
    $tags = App\Lipido::where('short_name', 'like', $term . '%')->lists('short_name', 'id');
    $valid_tags = [];
    foreach ($tags as $id => $tag) {
        $valid_tags[] = ['id' => $id, 'text' => $tag];
    }
    return \Response::json($valid_tags);
});

// Route::get('/peptido/{peptido_id}', 'PeptidosController@show')->name('peptidos.show');

/* TODO: Implementing a route for lipids
/
*/
// Route::get('/lipid/{lipid_id}', 'LipidosController@show')->name('lipid.show');
// Temporary route for lipid details using a closure with dummy data
// In a real application, this should be replaced with a proper controller method
// that fetches lipid details from the database.
// Example: Route::get('/lipid/{lipid_id}', 'LipidosController@show')->name('lipid.show');

Route::get('/lipid/{lipid_id}', [LipidController::class, 'show']
)->name('lipid.show');

// ion
// agua
// molecula

// resultado buscador
