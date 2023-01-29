<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeBanner extends Controller
{
    public function welcome() {
      return view('welcome');
    }
}
