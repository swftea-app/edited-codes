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

//Route::middleware('auth:api')->get('/betsystem', function (Request $request) {
//    return $request->user();
//});
Route::middleware('auth:api')->get('games/all', 'BetSystemController@all');
Route::middleware('auth:api')->get('games/{game_id}', 'BetSystemController@group');
Route::middleware('auth:api')->post('games/{game_id}/bidnow', 'BetSystemController@bidnow');
