<?php

namespace Modules\AccountHistory\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AccountHistory\Entities\AccountHistory;
use Modules\UserSystem\Entities\User;

class AccountHistoryController extends Controller {
  protected $valid_history_searched_types = ['All','Gift','Transfer','Emoticons','Trail','Guess Game','Lowcard Game'];
   public function getAccountHistory() {
     return AccountHistory::where('user_id','=', auth()->user()->id)->orderBy('id','DESC')->paginate(10);
   }
  public function getSearchedAccountHistory(Request $request) {
     if($request->has('type') && $request->has('from') && $request->has('to')) {
       $type = $request->type;
       $from = $request->from;
       $to = $request->to;
       if(!in_array($type, $this->valid_history_searched_types)) {
         return [
           'error' => true,
           'message' => 'Invalid type'
         ];
       }
       try {
         $from_date = Carbon::createFromFormat("d-m-Y",$from);
         $to_date = Carbon::createFromFormat("d-m-Y",$to);
         if($type == 'All') {
           return AccountHistory::where('created_at', ">=", $from_date->toDateTimeString())
             ->where("created_at","<=", $to_date->toDateTimeString())
             ->where('user_id','=',auth()->user()->id)
             ->orderBy('id','DESC')
             ->paginate(25);
         } else {
           return AccountHistory::where('created_at', ">=", $from_date->toDateTimeString())
             ->where("created_at","<=", $to_date->toDateTimeString())
             ->where('user_id','=',auth()->user()->id)
             ->where('type', '=', strtolower($type))
             ->orderBy('id','DESC')
             ->paginate(25);
         }
       } catch (\Exception $exception) {
         return [
           "error" => true,
           "message" => $exception->getMessage()
         ];
       }
     }
    return AccountHistory::where('user_id','=', auth()->user()->id)->orderBy('id','DESC')->paginate(10);
  }
}
