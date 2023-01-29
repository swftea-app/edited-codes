<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'swfteamission','middleware' => 'auth:api'], function () {
  Route::get('/', 'SwfteaMissionController@all');
  Route::get('getseason/{id}', 'SwfteaMissionController@season');
  Route::get('join/{id}', 'SwfteaMissionController@join');
  Route::get('collect/{weekid}/{taskid}', 'SwfteaMissionController@collect');
  Route::get('grab/{milestone_id}', 'SwfteaMissionController@grabmainpoints');
});