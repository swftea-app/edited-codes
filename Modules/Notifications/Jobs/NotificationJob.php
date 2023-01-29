<?php

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\Games\Entities\Leaderboard;
use Modules\Notifications\Entities\Notification;
use Modules\UserSystem\Entities\User;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $model;
    public $type;
    public function __construct($type, $model) {
      $this->type = $type;
      $this->model = $model;
    }
    public function handle() {
      switch ($this->type) {
        case 'gift':
          // Create notification for gift type
          $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
          $notification = new Notification();
          $notification->title = "New gift!!";
          $notification->description = $user->username." sent you a ".$this->model->name.". Hurray!!";
          $notification->user_id = $this->model->receiver_id;
          $notification->avatar = $this->model->gift_url;
          $notification->save();

          Leaderboard::create([
            'username' => $user->username,
            'type' => 'gift',
          ]);
          break;
        case 'merchantship':
          $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
          $notification = new Notification();
          $notification->title = $user->username." requested you for merchantship program. Please take action once payment is successful.";
          $notification->user_id = $this->model->under_of;
          $notification->avatar = getIcon('info');
          $notification->save();

          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "Your application for merchantship program is successfully received. You will be notified when your mentor takes action to your application.";
          $notification->user_id = $this->model->user_id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'mentorship':
          $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
          $notification = new Notification();
          $notification->title = $user->username." requested you for mentorship program. Please take action once payment is successful.";
          $notification->user_id = $this->model->under_of;
          $notification->avatar = getIcon('info');
          $notification->save();

          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "Your application for mentorship program is successfully received. You will be notified when your mentorhead takes action to your application.";
          $notification->user_id = $this->model->user_id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'promotion_accepted':
          if($this->model->type == 'merchantship') {
            $notification = new Notification();
            $notification->title = "Congratulations !!";
            $notification->description = "Your application for merchantship program is accepted. You will receive the benefits of this program for 1 month starting from this saturday midnight (+05:45 GMT). Thank you.";
            $notification->user_id = $this->model->user_id;
            $notification->avatar = getIcon('info');
            $notification->save();

            $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
            $merchant_cost = config('program.amount_to_be_merchant');
            $notification = new Notification();
            $notification->title = "Congratulations !!";
            $notification->description = "Your request of ".$user->username.' for a merchantship program is registered officially. The amount '.$merchant_cost.' credits is deducted from you. This amount will be transferred once '.$user->username.' is officially registered as merchant on this weekend. Happy Swifting.';
            $notification->user_id = $this->model->under_of;
            $notification->avatar = getIcon('info');
            $notification->save();
          }
          if($this->model->type == 'mentorship') {
            $notification = new Notification();
            $notification->title = "Congratulations !!";
            $notification->description = "Your application for mentorship program is accepted. You will receive the benefits of this program for 1 month starting from this saturday midnight (+05:45 GMT). Thank you.";
            $notification->user_id = $this->model->user_id;
            $notification->avatar = getIcon('info');
            $notification->save();

            $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
            $merchant_cost = config('program.amount_to_be_mentor');
            $notification = new Notification();
            $notification->title = "Congratulations !!";
            $notification->description = "Your request of ".$user->username.' for a mentorship program is registered officially. The amount '.$merchant_cost.' credits is deducted from you. This amount will be transferred once '.$user->username.' is officially registered as mentor on this weekend. Happy Swifting.';
            $notification->user_id = $this->model->under_of;
            $notification->avatar = getIcon('info');
            $notification->save();
          }
          break;
        case 'promotion_executed_accepted':
          if($this->model->type == 'merchantship') {
            $notification = new Notification();
            $notification->title = "Congratulations!!";
            $notification->description = "Your request for merchantship is approved by Administrators. Please visit Merchant Panel for further information.";
            $notification->user_id = $this->model->user_id;
            $notification->navigate = "Merchant Panel";
            $notification->avatar = getIcon('info');
            $notification->params = [];
            $notification->save();

            $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
            $notification = new Notification();
            $notification->title = "Congratulations!!";
            $notification->description = "Your request of ".$user->username.' for a merchantship program is approved officially. This amount previously deducted from your account is transferred and automatically tagged. Happy Swifting.';
            $notification->user_id = $this->model->under_of;
            $notification->avatar = getIcon('info');
            $notification->save();
          }
          if($this->model->type == 'mentorship') {
            $notification = new Notification();
            $notification->title = "Congratulations!!";
            $notification->description = "Your request for mentorship is approved by Administrators. Please visit Mentor Panel for further information.";
            $notification->user_id = $this->model->user_id;
            $notification->navigate = "Mentor Panel";
            $notification->avatar = getIcon('info');
            $notification->params = [];
            $notification->save();

            $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
            $notification = new Notification();
            $notification->title = "Congratulations!!";
            $notification->description = "Your request of ".$user->username.' for a mentorship program is approved officially. This amount previously deducted from your account is transferred and automatically tagged. Happy Swifting.';
            $notification->user_id = $this->model->under_of;
            $notification->avatar = getIcon('info');
            $notification->save();
          }
          break;
        case 'color_renew_warning':
          $notification = new Notification();
          $notification->title = "Reminder!!";
          $notification->description = "Your ".$this->model->role." program is expiring ".$this->model->expire_after.". You need more ".($this->model->target_point - $this->model->current_point)." point to save your color. Please renew in time.";
          $notification->user_id = $this->model->user_id;
          $notification->navigate = $this->model->role." Panel";
          $notification->avatar = getIcon('warn');
          $notification->params = [];
          $notification->save();

          $notification = new Notification();
          $notification->title = "Reminder!!";
          $notification->description = 'Your tagged '.$this->model->role.' ('.$this->model->username.') is expiring '.$this->model->expire_after.'. Let him/her know.';
          $notification->user_id = $this->model->tag_id;
          $notification->avatar = getIcon('warn');
          $notification->save();
          break;
        case 'color_renew_done':
          $notification = new Notification();
          $notification->title = "Congratulations!!";
          $notification->description = "Your ".$this->model->role." program is automatically renewed.";
          $notification->user_id = $this->model->user_id;
          $notification->navigate = $this->model->role." Panel";
          $notification->avatar = getIcon('info');
          $notification->params = [];
          $notification->save();

          $notification = new Notification();
          $notification->title = "Congratulations!!";
          $notification->description = 'Your tagged '.$this->model->role.' ('.$this->model->username.') is automatically renewed.';
          $notification->user_id = $this->model->tag_id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'color_renew_successful':
          $notification = new Notification();
          $notification->title = "Congratulations!!";
          $notification->description = "Your ".$this->model->role." program is safe for next month.";
          $notification->user_id = $this->model->user_id;
          $notification->navigate = $this->model->role." Panel";
          $notification->avatar = getIcon('info');
          $notification->params = [];
          $notification->save();

          $notification = new Notification();
          $notification->title = "Congratulations!!";
          $notification->description = 'Your tagged '.$this->model->role.' ('.$this->model->username.') is safe for next month.';
          $notification->user_id = $this->model->tag_id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'color_renew_not_done':
          $notification = new Notification();
          $notification->title = "Program Expired!!";
          $notification->description = "Your ".$this->model->role." program has expired.";
          $notification->user_id = $this->model->user_id;
          $notification->avatar = getIcon('warn');
          $notification->save();
          break;
        case 'tag_expired':
          $notification = new Notification();
          $notification->title = "Tag Expired!!";
          $notification->description = "Your tag for ".$this->model->me->username." has expired.";
          $notification->user_id = $this->model->under_of->id;
          $notification->avatar = getIcon('warn');
          $notification->save();
          break;
        case 'promotion_executed_rejected':
          $notification = new Notification();
          $notification->title = "Your request for promotion for ".$this->model->type." is rejected by administrators.";
          $notification->user_id = $this->model->user_id;
          $notification->avatar = getIcon('warn');
          $notification->save();

          $user = DB::table('users')->select(['username'])->where('id','=',$this->model->user_id)->first();
          $notification = new Notification();
          $notification->title = "Your request for user ".$user->username." for promotion on ".$this->model->type." is rejected.";
          $notification->user_id = $this->model->under_of;
          $notification->avatar = getIcon('warn');
          $notification->save();
          break;
        case 'promotion_rejected':
          $notification = new Notification();
          $notification->title = "Your request for promotion for ".$this->model->type." is rejected.";
          $notification->user_id = $this->model->user_id;
          $notification->avatar = getIcon('warn');
          $notification->save();
          break;
        case 'friend_request_sent':
          $from = $this->model->from;
          $to = $this->model->to;
          $notification = new Notification();
          $notification->title = "New friend request!";
          $notification->description = $from->username." wants to be your friend. Respond to his request.";
          $notification->user_id = $to->id;
          $notification->navigate = "Profile";
          $notification->avatar = getIcon('friend');
          $notification->params = [
            "user" => $from->id
          ];
          $notification->save();
          break;
        case 'like_notification':
          $from = $this->model->from;
          $to = $this->model->to;
          $notification = new Notification();
          $notification->title = "New like!";
          $notification->description = $from->username." has recently liked your profile.";
          $notification->user_id = $to->id;
          $notification->navigate = "Profile";
          $notification->avatar = getIcon('like');
          $notification->params = [
            "user" => $from->id
          ];
          $notification->save();
          break;
        case 'trail_notification':
          $to = $this->model->user_id;
          $notification = new Notification();
          $notification->title = "Trail received!!";
          $notification->avatar = getIcon('info');
          if($this->model->secondary_trail > 0) {
            $notification->description = "You have received ".$this->model->secondary_trail." credits as secondary trail.";
          } else {
            $notification->description = "You have received ".$this->model->amount." credits primary trail from tagged users.";
          }
          $notification->user_id = $to;
          $notification->navigate = "Histories";
          $notification->params = [
          ];
          $notification->save();
          break;
        case 'level_update':
          $to = $this->model->user_id;
          $notification = new Notification();
          $notification->title = "Level updated!!";
          $notification->avatar = getIcon('info');
          $notification->description = "Congratulations!! Your level is updated to ".$this->model->value;
          $notification->user_id = $to;
          $notification->save();
          break;
        case 'account_suspended':
          $to = $this->model->user_id;
          $notification = new Notification();
          $notification->title = "Account Suspended";
          $notification->avatar = getIcon('warn');
          $notification->description = "Your account is suspended permanently from SwfTea.";
          $notification->user_id = $to;
          $notification->save();
          break;
        case 'new_announcement_added':
          $users = DB::table('users')->select('id')->get();
          foreach ($users as $user) {
            $to = $user->id;
            $notification = new Notification();
            $notification->title = "New Announcement";
            $notification->avatar = getIcon('info');
            $notification->description = "New announcement is added on the system. Click here to view.";
            $notification->user_id = $to;
            $notification->navigate = "Announcements";
            $notification->params = [];
            $notification->save();
          }
          break;
        case 'admin_info_notification':
          $message = $this->model->message;
          $title = $this->model->title;
          $notification = new Notification();
          $notification->title = $title;
          $notification->description = $message;
          $notification->user_id = 1;
          $notification->avatar = getIcon('info');
          $notification->save();
          $message = $this->model->message;
          $title = $this->model->title;
          $notification = new Notification();
          $notification->title = $title;
          $notification->description = $message;
          $notification->user_id = 582;
          $notification->avatar = getIcon('info');
          $notification->save();
          $message = $this->model->message;
          $title = $this->model->title;
          $notification = new Notification();
          $notification->title = $title;
          $notification->description = $message;
          $notification->user_id = 80;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
      }
    }
}
