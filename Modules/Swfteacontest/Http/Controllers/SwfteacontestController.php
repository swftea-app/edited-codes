<?php

namespace Modules\Swfteacontest\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Swfteacontest\Entities\SwfteaContest;

class SwfteacontestController extends Controller
{
    public function all() {
      return SwfteaContest::where('resolved','=',false)->with(['terms'])->orderBy('id','DESC')->paginate(25);
    }
}
