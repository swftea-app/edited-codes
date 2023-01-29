<?php

Route::group(['prefix' => 'auth'], function () {
  // Authentication Routes...
  Route::get('login', 'LoginController@showLoginForm')->name('login');
  Route::post('login', 'LoginController@login');
  Route::post('logout', 'LoginController@logout')->name('logout');

// Registration Routes...
//  Route::get('register', 'RegisterController@showRegistrationForm')->name('register');
//  Route::post('register', 'RegisterController@register');

// Password Reset Routes...
  Route::get('password/reset', 'ForgotPasswordController@showLinkRequestForm')->name('password.request');
  Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
  Route::get('password/reset/{token}', 'ResetPasswordController@showResetForm')->name('password.reset');
  Route::post('password/reset', 'ResetPasswordController@reset')->name('password.update');

// Confirm Password (added in v6.2)
  Route::get('password/confirm', 'ConfirmPasswordController@showConfirmForm')->name('password.confirm');
  Route::post('password/confirm', 'ConfirmPasswordController@confirm');
//
//// Email Verification Routes...
//  Route::get('email/verify', 'VerificationController@show')->name('verification.notice');
//  Route::get('email/verify/{id}/{hash}', 'VerificationController@verify')->name('verification.verify');
//  Route::get('email/resend', 'VerificationController@resend')->name('verification.resend');
});

Route::get('/password/reset/done','ProfileController@passwordResetDone');
