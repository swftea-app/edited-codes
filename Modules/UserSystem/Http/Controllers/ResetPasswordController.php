<?php

namespace Modules\UserSystem\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;


class ResetPasswordController extends Controller {
  use ResetsPasswords;
  protected $redirectTo = '/password/reset/done';
  public function __construct() {
    $this->middleware('guest');
  }
  public function showResetForm(Request $request, $token = null){
    return view('usersystem::passwords.reset')->with(
      ['token' => $token, 'email' => $request->email]
    );
  }
  protected function resetPassword($user, $password) {
    $this->setUserPassword($user, $password);
    $user->setRememberToken(Str::random(60));
    $user->save();
    event(new PasswordReset($user));
  }
}