<?php

Route::middleware('auth:api')->get('/sync', 'ChatMiniController@syncAllConfigs');
Route::middleware('auth:api')->get('/sync/hidden', 'ChatMiniController@hiddenData');
Route::middleware('auth:api')->get('/announcements', 'ChatMiniController@announcements');
Route::middleware('auth:api')->get('/leaderboards', 'ChatMiniController@leaderboards');
Route::middleware('auth:api')->get('/leaderboard/{type}', 'ChatMiniController@leaderBoard');

Route::middleware('auth:api')->post('/purchaseCoins', 'ChatMiniController@purchaseCoins');
Route::middleware('auth:api')->get('/fetchRewardData', 'ChatMiniController@rewardCallback');
Route::middleware('auth:api')->get('/purchaseRewardCoins', 'ChatMiniController@getReward');
Route::middleware('auth:api')->get('/coinPackages', 'ChatMiniController@coinPackages');