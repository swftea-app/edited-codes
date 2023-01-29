<?php

namespace Modules\Custompage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Custompage\Entities\Page;

class CustompageController extends Controller {
    public function index($slug) {
      if($slug == 'privacy-policy') {
        return view('custompage::privacy-policy');
      }
      $page = Page::where('slug','=', $slug)->first();
      if(!$page) {
        abort(404);
      }
      return view('custompage::index')->with([
        'data' => $page
      ]);
    }
}
