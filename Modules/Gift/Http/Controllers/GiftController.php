<?php

namespace Modules\Gift\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Modules\Gift\Entities\AllGifts;

class GiftController extends Controller {
    public function all(Request $request) {
      if($request->has('search') && $request->has('discount')) {
        $search = $request->search;
        $discount = $request->discount;
        if(empty($search)) {
          if($discount) {
            return  AllGifts::where("discount",">",0)->where('visible', '=', 1)->paginate(25);;
          }
          return AllGifts::paginate(25);
        } else {
          if($discount) {
            return  AllGifts::where('name','LIKE','%'.$search.'%')->where('visible', '=', 1)->where("discount",">",0)->paginate(25);
          }
          return  AllGifts::where('name','LIKE','%'.$search.'%')->where('visible', '=', 1)->paginate(25);
        }
      }
    }
}
