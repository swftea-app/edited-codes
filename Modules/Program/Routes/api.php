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

Route::middleware('auth:api')->prefix("/merchant")->group(function () {
  Route::get('panel','ProgramController@merchantpanel');
  Route::post('requestForMerchantship','ProgramController@requestForMerchantship');
  Route::post('myApplicationList','ProgramController@myAppliedList');
});
Route::middleware('auth:api')->prefix("/mentor")->group(function () {
  Route::get('panel','ProgramController@mentorpanel');
  Route::post('requestForMentorship','ProgramController@requestForMentorship');
  Route::post('myApplicationList','ProgramController@myAppliedList');
  Route::post('myActionList','ProgramController@myActionList');
  Route::post('takeAction','ProgramController@takeAction');
});