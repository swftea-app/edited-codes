<?php

namespace Modules\Level\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\Badge\Jobs\BadgeJob;
use Modules\Level\Entities\Level;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\OnlineUsers;
use Modules\UserSystem\Entities\Profile;
use Modules\UserSystem\Entities\User;

class LevelJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $action;
    public $info;
    public $user;
    public function __construct($action, $user, $info) {
        $this->action = $action;
        $this->info = $info;
        $this->user = $user;
    }
    public function handle() {
        switch ($this->action) {
          case 'add bar':
            $user = User::where("id","=",$this->user)->with(['profile','level'])->first();
            $current_bar = $user->profile->level_bar;
            $amount = $this->info->amount + $current_bar;
            $credit = property_exists($this->info, "credit") ? $this->info->credit : 0;
            $old_spend = $user->profile->spent_for_next_level;
            $todays_old_spend = $user->profile->today_spent_amount;
            if($amount > 0) {
              DB::table('profiles')->where('user_id', '=',$this->user)->update([
                "level_bar" => $amount,
                "spent_for_next_level" => $old_spend + $credit,
                "today_spent_amount" => $todays_old_spend + $credit
              ]);
            }
            if(canUpdateLevel($user->level, $amount)) {
              // Level updated..
              dispatch(new LevelJob('update level', $this->user, $this->info));
            }
            break;
          case 'update level':
            $user = User::where("id","=",$this->user)->with(['profile','level'])->first();

            if(canUpdateLevel($user->level, $user->profile->level_bar)) {
              $levels = config('level.all_levels');
              $new_level = $user->level->value + 1;
              $level = $levels[$new_level];
              Level::create([
                'name' => $level,
                'value' => $new_level,
                'user_id' => $this->user,
              ]);

              dispatch(
                new SeasonPoint(
                  'add points',
                  $this->user,
                  'Primarylevel',
                  'upgrade_level',
                  1)
              )->onQueue('low');

              DB::table('profiles')->where("user_id","=", $this->user)->update([
                "level_bar" => 0,
                "spent_for_next_level" => 0
              ]);

              # Award user
              $winning_amount = getBonusForLevelUpdate($new_level);
              $user->histories()->create([
                'type' => 'Transfer',
                'creditor' => "System",
                'creditor_id' => 1,
                'message' => "Reward for reaching level ".($new_level),
                'old_value' => $user->credit,
                'new_value' => $user->credit + $winning_amount,
                'user_id' => $user->id
              ]);

              DB::table('users')
                ->where('id','=',$user->id)
                ->increment('credit', $winning_amount);


              OnlineUsers::where('user_id','=',$user->id)->delete();
              OnlineUsers::create([
                'user_id' => $user->id
              ]);

              // Badge
              # For LEVEL
              switch ($new_level) {
                case 25:
                  dispatch(new BadgeJob('add badge',$user->id, 'bronze_badge'));
                  break;
                case 50:
                  dispatch(new BadgeJob('add badge',$user->id, 'silver_badge'));
                  break;
                case 75:
                  dispatch(new BadgeJob('add badge',$user->id, 'golden_badge'));
                  break;
                case 100:
                  dispatch(new BadgeJob('add badge',$user->id, 'diamond_badge'));
                  break;
                default:
              }
            }
            break;
        }
    }
}
