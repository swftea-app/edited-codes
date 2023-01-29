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

Route::middleware('auth:api')->post('/inappmail/sendEmail','InAppMailController@sendEmail');
Route::middleware('auth:api')->get('/inappmail/inbox','InAppMailController@inbox');
Route::middleware('auth:api')->get('/inappmail/sentmail','InAppMailController@sentEmail');
Route::middleware('auth:api')->get('/inappmail/read/{id}','InAppMailController@read');
Route::middleware('auth:api')->get('/inappmail/delete/{id}','InAppMailController@delete');
Route::middleware('auth:api')->get('/inappmail/sentMail/delete/{id}','InAppMailController@deleteSent');