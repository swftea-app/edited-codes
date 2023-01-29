<?php

namespace Modules\Notifications\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Entities\Notification;

class NotificationsController extends Controller {
    public function getAllNotifications() {
      $all_notifications = Notification::where("user_id","=", auth()->user()->id)
        ->orderBy("status", "ASC")
        ->orderBy("id", "DESC")
        ->paginate(100);
      DB::table('notifications')->where("user_id","=",auth()->user()->id)->where("status","=",0)->update(['status' => 1]);
      return $all_notifications;
    }
}
