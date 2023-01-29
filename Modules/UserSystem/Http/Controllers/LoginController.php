<?php

namespace Modules\UserSystem\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\LoginTracker\Entities\LoginTracker;
use Modules\UserSystem\Entities\User;
use Pusher\Pusher;
use Stevebauman\Location\Facades\Location;

class LoginController extends Controller
{
  use AuthenticatesUsers;
  public $successStatus = 200;

  public function __construct()
  {
    $this->middleware('guest')->except('logout');
  }

  protected function authenticated()
  {
    return redirect(route('dashboard.index'));
  }

  public function showLoginForm()
  {
    return view('usersystem::login');
  }

  /* For API */
  public function apiLogin(Request $request)
  {
    # validate username and password
    $validator = Validator::make($request->all(), [
      'username' => 'required|min:3',
      'password' => 'required|min:3',
    ]);
    if ($validator->fails()) {
      return response()->json([
        'error' => true,
        'open_verify_email' => false,
        'message' => "Validation error!"
      ]);
    }
    $user = User::where('username','=', \request('username'))->first();
    if(!$user) {
      return [
        'error' => true,
        'open_verify_email' => false,
        'message' => 'Not a valid user'
      ];
    }
    if($user->status == 0) {
      return [
        'error' => true,
        'open_verify_email' => false,
        'message' => 'Your account is suspended.'
      ];
    }
//    if(!in_array($user->id, [1, 80, 53, 56, 49, 59, 60, 43, 47, 582, 61, 42, 66, 51, 1072])) {
//      return [
//        'error' => true,
//        'open_verify_email' => false,
//        'message' => 'We are updating system. Visit https://swftea.com for latest update. Thank you.'
//      ];
//    }
    if (Auth::attempt([
      'username' => request('username'),
      'password' => request('password')
    ])) {
      if($user->email_verified_at == NULL) {
        return [
          'error' => true,
          'open_verify_email' => true,
          'message' => 'Please verify your email'
        ];
      }
      $user = Auth::user();
      $tokenResult = $user->createToken('Personal Access Token');
      $token = $tokenResult->token;
      $token->expires_at = Carbon::now()->addWeeks(500);
      $token->save();

      # Track login
      $login_tracker = new LoginTracker();
      $ip = \request()->ip();
      $location = Location::get($ip);
      $login_tracker->ip = $ip;
      $login_tracker->countryName = $location->countryName;
      $login_tracker->countryCode = $location->countryCode;
      $login_tracker->cityName = $location->cityName;
      $login_tracker->latitude = $location->latitude;
      $login_tracker->longitude = $location->longitude;
      $login_tracker->action = "login";
      $login_tracker->device_type = "Mobile";
      $login_tracker->device_name = $request->has('device_name') ? $request->device_name : \request()->userAgent();
      $login_tracker->device_id = $request->has('device_id') ? $request->device_id : null;
      $login_tracker->user_id = $user->id;
      $login_tracker->save();
      cache(['reward_start_time_'.$user->id => time()], now()->addCenturies(500));
      cache(['reward_level_'.$user->id => 1], now()->addCenturies(500));
      #end login tracker

      return response()->json([
        'error' => false,
        'access_token' => $tokenResult->accessToken,
        'token_type' => 'Bearer',
        'user' => $user,
        'expires_at' => Carbon::parse(
          $tokenResult->token->expires_at
        )->toDateTimeString()
      ]);
    } else {
      return response()->json([
        'error' => true,
        'message' => 'Username and password not matched.'
      ]);
    }
  }


  public function pusherLogin(Request $request) {
    if(!Auth::check()) {
      return response("Forbidden", 403);
    }
    $pusher = new Pusher(
      config('broadcasting.connections.pusher.key'),
      config('broadcasting.connections.pusher.secret'),
      config('broadcasting.connections.pusher.app_id'),
      config('broadcasting.connections.pusher.options')
    );
    try {
      return $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
    } catch (\Exception $exception) {
      return $exception;
    }
  }

  public function logout(Request $request)
  {
    $request->user()->token()->revoke();
    return response()->json([
      'error' => false,
      'message' => 'Successfully logged out'
    ]);
  }
}