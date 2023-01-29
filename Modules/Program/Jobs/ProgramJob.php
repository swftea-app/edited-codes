<?php

namespace Modules\Program\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\UserSystem\Entities\User;

class ProgramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $action;
    public $user_id;
    public function __construct($action, $user_id) {
        $this->action = $action;
        $this->user_id = $user_id;
    }
    public function handle() {
//        switch ($this->action) {
//          case 'first inspect program':
//            $user = User::where('id','=', $this->user_id)->first();
//            $user_roles = $user->roles->pluck("name")->toArray();
//            $color_role = in_array("Merchant", $user_roles) ? "Merchant" : "Mentor";
//            if($user->program_point >= getProgramPointLimit($user_roles)) {
//              dispatch(new NotificationJob("color_renew_successful",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id,
//                "username" => $user->username,
//                "tag_id" => $user->username,
//              ]));
//              dispatch(new ProgramJob("renew color", $user->id))->delay(now()->addSeconds(config('program.program_config.inspection_interval') * 2));
//            } else {
//              dispatch(new NotificationJob("color_renew_warning",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id,
//                "username" => $user->username,
//                "tag_id" => $user->tag_id,
//                "expire_after" => now()->addSeconds(config('program.program_config.inspection_interval') * 2)->diffForHumans(),
//                "target_point" => getProgramPointLimit($user_roles),
//                "current_point" => $user->program_point
//              ]));
//              dispatch(new ProgramJob("second inspect program", $user->id))->delay(now()->addSeconds(config('program.program_config.inspection_interval')));
//            }
//            break;
//          case 'second inspect program':
//            $user = User::where('id','=', $this->user_id)->first();
//            $user_roles = $user->roles->pluck("name")->toArray();
//            $color_role = in_array("Merchant", $user_roles) ? "Merchant" : "Mentor";
//            if($user->program_point > getProgramPointLimit($user_roles)) {
//              dispatch(new NotificationJob("color_renew_successful",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id
//              ]));
//            } else {
//              dispatch(new NotificationJob("color_renew_warning",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id,
//                "username" => $user->username,
//                "tag_id" => $user->tag_id,
//                "expire_after" => now()->addSeconds(config('program.program_config.inspection_interval'))->diffForHumans(),
//                "target_point" => getProgramPointLimit($user_roles),
//                "current_point" => $user->program_point
//              ]));
//            }
//            dispatch(new ProgramJob("renew color", $user->id))->delay(now()->addSeconds(config('program.program_config.inspection_interval')));
//            break;
//          case 'renew color':
//            $user = User::where('id','=', $this->user_id)->first();
//            $user_roles = $user->roles->pluck("name")->toArray();
//            $color_role = in_array("Merchant", $user_roles) ? "Merchant" : "Mentor";
//            DB::table('users')->select(['tag_id'])->where('tag_id',"=", $this->user_id)->update([
//              'tag_id' => 1
//            ]);
//            if($user->program_point >= getProgramPointLimit($user_roles)) {
//              Artisan::call("chatmini:user", [
//                'op' => 'update',
//                '--user' => $this->user_id,
//                '--roles' => $color_role,
//              ]);
//              $tagger = User::where("id","=",$user->tag_id)->first();
//              $reward = 0;
//              if($color_role == "Merchant") {
//                $reward = config('program.amount_to_be_merchant') * 0.05;
//              }
//              if($color_role == "Mentor") {
//                $reward = config('program.amount_to_be_mentor') * 0.1;
//              }
//              $reward = round($reward);
//              $tagger->histories()->create([
//                'type' => 'Transfer',
//                'creditor' => 'system',
//                'creditor_id' => 1,
//                'message' =>  "Received bonus for renewing ".$user->username." on ".$color_role,
//                'old_value' => $tagger->credit,
//                'new_value' => $tagger->credit + $reward,
//                'user_id' => $tagger->id
//              ]);
//              DB::table('users')
//                ->where('id','=',$tagger->id)
//                ->increment('credit', $reward);
//              $expire_after = (config('program.program_config.inspection_interval') * 2) + config('program.program_config.inspection_first');
//              DB::table('users')->select(['tag_id'])->where('id',"=", $this->user_id)->update([
//                'program_expiry' => Carbon::now()->addSeconds($expire_after),
//                'tag_id' => 1,
//                'program_point' => 0
//              ]);
//              dispatch(new ProgramJob("first inspect program", $this->user_id))->delay(now()->addSeconds(config('program.program_config.inspection_first')));
//              dispatch(new NotificationJob("color_renew_done",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id,
//                "username" => $user->username,
//                "tag_id" => $user->tag_id,
//              ]));
//            } else {
//              DB::table('users')->select(['tag_id'])->where('id',"=", $this->user_id)->update([
//                'tag_id' => 1
//              ]);
//              Artisan::call("chatmini:user", [
//                'op' => 'update',
//                '--user' => $this->user_id,
//                '--roles' => $color_role,
//              ]);
//              dispatch(new NotificationJob("color_renew_not_done",(object)[
//                "role" => $color_role,
//                "user_id" => $this->user_id,
//              ]));
//            }
//            break;
//        }
    }
}
