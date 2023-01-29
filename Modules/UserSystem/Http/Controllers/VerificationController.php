<?php

namespace Modules\UserSystem\Http\Controllers;

use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Routing\Controller;

class VerificationController extends Controller {
  use VerifiesEmails;
  protected $redirectTo = '/';
  public function __construct() {
    $this->middleware('auth');
    $this->middleware('signed')->only('verify');
    $this->middleware('throttle:6,1')->only('verify', 'resend');
  }
  public function show() {
    return view('usersystem::verify');
  }
}