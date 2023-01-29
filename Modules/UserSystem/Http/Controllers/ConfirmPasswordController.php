<?php

namespace Modules\UserSystem\Http\Controllers;


use Illuminate\Foundation\Auth\ConfirmsPasswords;
use Illuminate\Routing\Controller;

class ConfirmPasswordController extends Controller {
  use ConfirmsPasswords;
  public function showConfirmForm() {
    return view('usersystem::passwords.confirm');
  }
}