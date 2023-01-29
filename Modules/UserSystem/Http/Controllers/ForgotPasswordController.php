<?php

namespace Modules\UserSystem\Http\Controllers;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller {
  use SendsPasswordResetEmails;
  public function __construct() {
    $this->middleware('guest');
  }
  public function resetPassword(Request $request) {
    $validator = Validator::make($request->all(), [
      'username' => 'required',
      'email' => 'required|email',
    ]);
    if($validator->fails()) {
      return [
        "error" => true,
        "message" => $validator->errors()->first()
      ];
    }
    #validate user
    $user = DB::table('users')->where('email','=',$request->email)->where('username','=',$request->username)->get()->count();
    if($user == 0) {
      return [
        "error" => true,
        "message" => 'Username and Email not found.'
      ];
    }
    # send reset link
    $this->sendResetLinkEmail($request);
    if(Password::RESET_LINK_SENT) {
      return [
        "error" => false,
        "message" => 'Please have a look to the email.'
      ];
    } else {
      return [
        "error" => true,
        "message" => 'Some error occurred.'
      ];
    }
  }
  public function showLinkRequestForm() {
    return view('usersystem::passwords.email');
  }
}