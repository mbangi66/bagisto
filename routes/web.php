<?php

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


use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('locations')->group(function () {
    // GET /locations/states/{countryId}
    Route::get('/states/{countryId}', [LocationController::class, 'getStates'])
         ->name('front.locations.states');

    // GET /locations/cities/{stateName}
    Route::get('/cities/{stateName}', [LocationController::class, 'getCities'])
         ->name('front.locations.cities');

    // GET /locations/blocks/{cityName}
    Route::get('/blocks/{cityName}', [LocationController::class, 'getBlocks'])
         ->name('front.locations.blocks');
});



Route::group(['prefix' => 'admin', 'middleware' => ['AdminAuth']], function () {

    // State Routes
    Route::get('state', [StateController::class, 'index']);
    Route::post('state/store', [StateController::class, 'store']);
    Route::get('state/list', [StateController::class, 'list']);
    Route::post('state/show', [StateController::class, 'show']);
    Route::post('state/update', [StateController::class, 'update']);
    Route::post('state/status', [StateController::class, 'status']);
    Route::post('state/delete', [StateController::class, 'delete']);

    // City Routes
    Route::get('city', [CityController::class, 'index']);
    Route::post('city/store', [CityController::class, 'store']);
    Route::get('city/list', [CityController::class, 'list']);
    Route::post('city/show', [CityController::class, 'show']);
    Route::post('city/update', [CityController::class, 'update']);
    Route::post('city/status', [CityController::class, 'status']);
    Route::post('city/delete', [CityController::class, 'delete']);

    // Block Routes
    Route::get('block', [BlockController::class, 'index']);
    Route::post('block/store', [BlockController::class, 'store']);
    Route::post('block/show', [BlockController::class, 'show']);
    Route::post('block/update', [BlockController::class, 'update']);
    Route::post('block/delete', [BlockController::class, 'delete']);
});

Route::get('/test-copy-powers', function () {
 
     $request = new \Illuminate\Http\Request();
     $request->replace(['powers' => [6.0, 5.5, 5.0]]);
     
     $controller = app()->make(\Webkul\Admin\Http\Controllers\Catalog\ProductController::class);
     return $controller->copyWithPowers($request, 296); // Replace 1 with your base product ID
 });
 