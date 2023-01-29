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



Route::prefix('/chatmini/server/webhooks/')->group(function() {
    Route::post('presenceChannels', 'ChatMiniController@presenceChannels');
    Route::post('clientEvents', 'ChatMiniController@clientEvents');
});

Route::prefix('/swfteashare/')->group(function() {
  Route::post('files', 'ChatMiniController@files');
  Route::get('files/{file}', 'ChatMiniController@download');
});
