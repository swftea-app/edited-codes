<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'auth'], function () {
  Route::post('login', 'LoginController@apiLogin');
  Route::post('register', 'RegisterController@apiRegister');
  Route::post('verifyEmail', 'ProfileController@verifyEmail');
  Route::post('resetPassword', 'ForgotPasswordController@resetPassword');
});
# For user api
Route::group(['middleware' => 'auth:api','prefix' => 'user'], function(){
  Route::get('profile/{user_id}', 'ProfileController@profile');
  Route::get('friends', 'ProfileController@getAllFriends');
  Route::get('creditInfo', 'ProfileController@getCredit');
  Route::get('getAccountInfo', 'ProfileController@getAccountInfo');
  Route::get('getLocationInfo', 'ProfileController@getLocationInfo');
  Route::post('updateStatus', 'ProfileController@updateStatus');
  Route::post('search', 'ProfileController@searchUser');
  Route::post('transfer', 'ProfileController@transfer');
  Route::post('updatePincode', 'ProfileController@updatePincode');
  Route::post('updatePassword', 'ProfileController@updateSystemPassword');
  Route::post('updatePicture', 'ProfileController@updatePicture');
  Route::post('updateSettings', 'ProfileController@updateSettings');
  Route::post('updateCoverPicture', 'ProfileController@updateCoverPicture');
  Route::get('findUsers/{type}', 'ProfileController@filterUserByRole');
  Route::get('getFriendRequests', 'ProfileController@getFriendRequests');


  Route::post('sendFriendRequest', 'ProfileController@sendFriendRequest');
  Route::post('acceptFriendRequest', 'ProfileController@acceptFriendRequest');
  Route::post('cancelFriendRequest', 'ProfileController@cancelFriendRequest');
  Route::post('rejectFriendRequest', 'ProfileController@rejectFriendRequest');
  Route::post('unfriend', 'ProfileController@unfriend');
  Route::post('like', 'ProfileController@like');
  Route::post('unlike', 'ProfileController@unlike');
});