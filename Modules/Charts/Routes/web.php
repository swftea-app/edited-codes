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

Route::prefix('charts')->middleware('auth')->group(function () {
  Route::prefix('api')->name('charts.')->group(function () {
    Route::get('{module?}/{type?}', 'ChartsController@chartDataApi')->name('api');
  });
});
