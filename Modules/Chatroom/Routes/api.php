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

Route::middleware('auth:api')->group(function() {
  Route::get('chatroom/{room_id}/join', 'ChatroomController@joinRoom');
  Route::get('chatroom/{room_id}/leave', 'ChatroomController@leaveRoom');
  Route::get('chatroom/{room_id}/info/{type}', 'ChatroomController@getInfo');
  Route::get('chatrooms/{type}', 'ChatroomController@getChatrooms');
  Route::post('chatroom/sendMessage', 'ChatroomController@sendMessage');

  Route::post('chatroom/search', 'ChatroomController@searchChatroom');

  Route::post('chatroom/create', 'ChatroomController@create');
  Route::post('chatroom/addAsFavourite', 'ChatroomController@addAsFavourite');
  Route::post('chatroom/removeFromFavourite', 'ChatroomController@removeFromFavourite');
});