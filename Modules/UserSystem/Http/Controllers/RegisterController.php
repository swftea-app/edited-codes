<?php

namespace Modules\UserSystem\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\UserSystem\Entities\Profile;
use Modules\UserSystem\Entities\User;
use Modules\UserSystem\Jobs\UserVerification;
use Spatie\Permission\Models\Role;
use Stevebauman\Location\Facades\Location;

class RegisterController extends Controller {
  use RegistersUsers;
  protected function redirectTo() {
    return '/administration/dashboard';
  }
  public function __construct() {
    $this->middleware('guest');
  }
  protected function validator(array $data) {
    return Validator::make($data, [
      'username' => ['required','string','regex:/^[a-z][a-z0-9._-]*$/','min:6','max:26','unique:users'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'ends_with:gmail.com,yahoo.com,icloud.com,outlook.com,ymail.com,hotmail.com,live.com'],
      'password' => ['required', 'string', 'min:6', 'confirmed'],
      'name' => ['required', 'string', 'max:255'],
      'gender' => ['required'],
    ]);
  }
  protected function create(array $data) {
    $user = User::create([
      'username' => $data['username'],
      'email' => strtolower($data['email']),
      'password' => \Hash::make($data['password']),
    ]);
    $user->gender = $data['gender'] == '1' ? 'Male' : 'Female';
    $user->name = $data['name'];
    $role = Role::findByName('User');
    $user->assignRole($role);
    // Assigning defaults
    $defaults = config('usersystem.defaults');
    foreach ($defaults as $relation => $default) {
      if(is_array($default)) {
        $user->{$relation}()->create($default);
      }
    }
    # once profile is created
    # for location
    $profile = Profile::where('id','=', $user->profile->id)->first();
    if($profile) {
      $position = Location::get();
      $profile->userIp = $position->ip;
      $profile->countryCode = $position->countryCode;
      $profile->latitude = $position->latitude;
      $profile->longitude = $position->longitude;
      # Token
      $token = rand(555555,9999999999);
      $profile->verification_token = $token;
      $profile->save();
      $user->country = $position->countryName;
      # send mail
      dispatch(new UserVerification('send verification mail',[
        'receiver' => strtolower($user->email),
        'token' => $token,
        'username' => $user->username,
      ]));
    }
    $user->credit = config('usersystem.defaults.credit', 108);
    $user->save();
    $user->histories()->create([
      'type' => 'Transfer',
      'creditor' => 'system',
      'creditor_id' => 1,
      'message' =>  "Received ".config('usersystem.defaults.credit', 108)." credits on registration.",
      'old_value' => 0,
      'new_value' => config('usersystem.defaults.credit', 108),
      'user_id' => $user->id
    ]);
    DB::table('emoticon_category_users')->insert([
      'user_id' => $user->id,
      'emotion_category_id' => 15,
    ]);
    DB::table('emoticon_category_users')->insert([
      'user_id' => $user->id,
      'emotion_category_id' => 12,
    ]);
    DB::table('emoticon_category_users')->insert([
      'user_id' => $user->id,
      'emotion_category_id' => 11,
    ]);
    return $user;
  }
  public function showRegistrationForm() {
    return "Noting here. Please go to app";
//    return view('usersystem::register');
  }
  /* For API */
  public function apiRegister(Request $request) {
    $validator = $this->validator($request->all());
    if($validator->fails()) {
      return response()->json([
        "error" => true,
        "message" => $validator->errors()->first()
      ]);
    }
    # Grab the request data
    $credentials = request([
      'username',
      'password',
      'email',
      'gender',
      'name',
    ]);
    $user = $this->create($credentials);
    # Auth user
//    Auth::loginUsingId($user->id);
//    $user = Auth::user();
//    $tokenResult = $user->createToken('Personal Access Token');
//    $token = $tokenResult->token;
//    $token->expires_at = Carbon::now()->addWeeks(500);
//    $token->save();

    return response()->json([
      'error' => false,
      'message' => 'Welcome '.$user->name.' ('.$user->username.'). We have sent you email for verification.'
    ]);
  }
}