<?php

namespace Modules\ChatMini\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lexx\ChatMessenger\Models\Thread;
use Modules\BetSystem\Entities\BettingGroup;
use Modules\Chat\Events\MessageSent;
use Modules\ChatMini\Entities\FreeToken;
use Modules\Chatroom\Entities\Chatroom;
use Modules\Chatroom\Events\SendAnnouncement;
use Modules\Games\Entities\Leaderboard;
use Modules\GroupChat\Events\NewMessageSent;
use Modules\Level\Jobs\LevelJob;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\Program\Entities\MerchantTag;
use Modules\SwfteaMission\Entities\MissionSeason;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\OnlineUsers;
use Modules\UserSystem\Entities\Profile;
use Modules\UserSystem\Entities\User;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ChatMiniServer extends Command
{
    protected $name = 'chatmini:server';
    protected $description = 'Command description.';
    protected $signature = 'chatmini:server {op} {action}';
    public function __construct() {
        parent::__construct();
    }
    public function handle() {
        $datas = [
          'action' => $this->argument("action"),
          'op' => $this->argument("op"),
        ];
        switch ($datas['op']) {
          case 'refresh':
            # Refresh data system
            switch ($datas['action']) {
              case 'kicked_list':
                # Clear chatroom kicked members
                $chatrooms = Chatroom::has("kickedMembers")->get();
                foreach ($chatrooms as $chatroom) {
                  $chatroom->kickedMembers()->detach();
                } // kicked list cleared
                break; //Kicked list
              case 'credit_transfer':
                DB::table('profiles')->where('today_transferred_amount','>',0)->update([
                  'today_transferred_amount' => 0
                ]);
                break; //Kicked list
              case 'messages':
                $chatrooms = Chatroom::has("messages")->get();
                foreach ($chatrooms as $chatroom) {
                  $chatroom->messages()->delete();
                } // messages cleared
                break; //Kicked list
              case 'announcements':
                # Clear chatroom kicked members
                $chatrooms = Chatroom::where("announcement","!=", NULL)->has('members')->get();
                foreach ($chatrooms as $chatroom) {
                  $chatroom->messages()->create([
                    'type' => 'announcement',
                    'raw_text' => $chatroom->announcement,
                    'full_text' => $chatroom->announcement,
                    'formatted_text' => $chatroom->announcement,
                    'user_id' => 1
                  ]);
                } // kicked list cleared
                break; //Kicked list
              case 'ipl_contest':
                $groups = BettingGroup::where('betting_category_id','=',4)->has('winner')->get();
                foreach ($groups as $model) {
                  $winners = [];
                  $players = [];
                  foreach ($model->teams()->get() as $win) {
                    if($win->id != $model->winner_id) {
                      foreach ($win->bets()->get() as $beter) {
                        $players[] = $beter->user->id;
                      }
                      continue;
                    }
                    foreach ($win->bets()->get() as $beter) {
                      $winners[] = $beter->user->id;
                    }
                  }


                  $winners = array_unique($winners);
                  $players = array_unique($players);
                  foreach ($winners as $winner) {
                    if (!in_array($winner, $players)) {
                      $user_name = DB::table('users')
                        ->select(['username'])
                        ->where('id', '=', $winner)
                        ->first();
                      Leaderboard::create([
                        'username' => $user_name->username,
                        'type' => 'ipl_contest',
                      ]);
                      Leaderboard::create([
                        'username' => $user_name->username,
                        'type' => 'ipl_contest',
                      ]);
                      Leaderboard::create([
                        'username' => $user_name->username,
                        'type' => 'ipl_contest',
                      ]);
                    }
                  }
                }
                break;
              case 'contest':
                $from_date = new Carbon('2020-07-24 12:00:00');
                $from_date->setTimezone('Asia/Kathmandu');
                $to_date = new Carbon('2020-07-27 00:00:00');
                $to_date->setTimezone('Asia/Kathmandu');
                $all_gifts_sender = \Illuminate\Support\Facades\DB::table('gifts')
                  ->where("name","=","ganesha")
                  ->groupBy('user_id')
                  ->selectRaw("count(*) as total, user_id")
                  ->where('created_at', ">=", $from_date->toDateTimeString())
                  ->where('created_at', "<=", $to_date->toDateTimeString())
                  ->get();
                \Illuminate\Support\Facades\DB::table('leaderboards')
                  ->where('type','=','ganesha')
                  ->delete();
                foreach ($all_gifts_sender as $value) {
                  $user = \Illuminate\Support\Facades\DB::table('users')
                    ->select(['username'])
                    ->where('id', '=', $value->user_id)
                    ->first();
                  $gifts = [];
                  for ($i = 0; $i < $value->total; $i++) {
                    $gifts[] = [
                      'username' => $user->username,
                      'type' => 'ganesha',
                      'updated_at' => date('Y-m-d H:i:s'),
                      'created_at' => date('Y-m-d H:i:s'),
                    ];
                  }
                  if (count($gifts)) {
                    \Modules\Games\Entities\Leaderboard::insert($gifts);
                  }
                }
                break;
              case "sync_gifts":
                $all_gifts = \Illuminate\Support\Facades\DB::table('gifts')
                  ->groupBy('user_id')
                  ->selectRaw("count(*) as total, user_id")
                  ->get();
                foreach ($all_gifts as $gift) {
                  \Illuminate\Support\Facades\DB::table('users')
                    ->where('id','=',$gift->user_id)
                    ->increment('gifts_count',$gift->total);
                  $sent_gift = \Illuminate\Support\Facades\DB::table('gifts')
                    ->where('user_id','=',$gift->user_id)
                    ->count();
                  \Illuminate\Support\Facades\DB::table('users')
                    ->where('id','=', $gift->user_id)
                    ->increment('sentgifts_count', $sent_gift);
                }
                DB::table('gifts')->truncate();
                break;
              case 'picker':
                $picker_code = getPickerCode();
                $picker_amount = getPickerAmount();
                $picker_max_users = getMaxNumberPicker();
                $valid_time = rand(45,60);
                $message = 'Hurray!! you have '.$valid_time.' seconds to earn the SWFTEA earning code of '.$picker_code.'. This is only valid to first '.$picker_max_users.' users. Type /earn to the earning code. (Amount: '.$picker_amount.' credits)';
                $token = new FreeToken();
                $token->title = 'Free token (Auto)';
                $token->description = 'Free token generated on '.now()->format('d m, Y');
                $token->token = $picker_code;
                $token->amount = $picker_amount;
                $token->max_user = $picker_max_users;
                $token->expires_on = now()->addSeconds($valid_time);
                $token->type = 'global';
                $token->save();
                $chatrooms = Chatroom::where('user_id','=',1)->get();
                foreach ($chatrooms as $chatroom) {
                  $extra_info = [];
                  $chatroom->messages()->create([
                    'type' => 'info',
                    'raw_text' => $message,
                    'full_text' => $message,
                    'formatted_text' => $message,
                    'user_id' => 1,
                    'extra_info' => $extra_info
                  ]);
                }
                break;
              case 'trails':
                # trails rate
                $hmt_rate = 0.02;
                $mentor_rate = 0.025;
                $merchant_rate = 0.03;
                # Compound trails
                $hmt_crate = 0.15;
                $mentor_crate = 0.10;
                // Mentor Head
                $mentor_heads = User::whereHas('roles', function ($q) {
                  $q->where("name", "Mentor Head");
                })->with('tags.profile')->get();
                foreach ($mentor_heads as $mentor_head) {
                  $trail_amount = 0;
                  foreach ($mentor_head->tags as $tag) {
                    $trail_amount += $tag->profile->today_spent_amount;
                  }
                  $trail_amount = round($trail_amount * $hmt_rate);
                  if($trail_amount > 0) {
                    $mentor_head->histories()->create([
                      'type' => 'Trail',
                      'creditor' => 'system',
                      'creditor_id' => 1,
                      'message' =>  "Received ".$trail_amount." credits as primary trail.",
                      'old_value' => $mentor_head->credit,
                      'new_value' => $mentor_head->credit + $trail_amount,
                      'user_id' => $mentor_head->id
                    ]);
                    DB::table('users')
                      ->where('id','=',$mentor_head->id)
                      ->increment('credit', $trail_amount);
                    dispatch(new NotificationJob('trail_notification', (object) [
                      "amount" => $trail_amount,
                      "secondary_trail" => 0,
                      "user_id" => $mentor_head->id
                    ]));
                  }
                }
                // Mentor
                $mentors = User::whereHas('roles', function ($q) {
                  $q->where("name", "Mentor");
                })->with('tags.profile')->get();
                foreach ($mentors as $mentor) {
                  $trail_amount = 0;
                  foreach ($mentor->tags as $tag) {
                    $trail_amount += $tag->profile->today_spent_amount;
                  }
                  $trail_amount = round($trail_amount * $mentor_rate);
                  if($trail_amount > 0) {
                    $mentor->histories()->create([
                      'type' => 'Trail',
                      'creditor' => 'system',
                      'creditor_id' => 1,
                      'message' =>  "Received ".$trail_amount." credits as primary trail.",
                      'old_value' => $mentor->credit,
                      'new_value' => $mentor->credit + $trail_amount,
                      'user_id' => $mentor->id
                    ]);
                    DB::table('users')
                      ->where('id','=',$mentor->id)
                      ->increment('credit', $trail_amount);
                    dispatch(new NotificationJob('trail_notification', (object) [
                      "amount" => $trail_amount,
                      "secondary_trail" => 0,
                      "user_id" => $mentor->id
                    ]));
                    # Secondary trail
                    $head_mentor = User::where("id","=",$mentor->tag_id)->first();
                    $head_mentor->histories()->create([
                      'type' => 'Trail',
                      'creditor' => 'system',
                      'creditor_id' => 1,
                      'message' =>  "Received ".round($trail_amount * $hmt_crate)." credits as secondary trail.",
                      'old_value' => $head_mentor->credit,
                      'new_value' => $head_mentor->credit + round($trail_amount * $hmt_crate),
                      'user_id' => $head_mentor->id
                    ]);
                    DB::table('users')
                      ->where('id','=',$head_mentor->id)
                      ->increment('credit', round($trail_amount * $hmt_crate));
                    dispatch(new NotificationJob('trail_notification', (object) [
                      "amount" => $trail_amount,
                      "secondary_trail" => round($trail_amount * $hmt_crate),
                      "user_id" => $head_mentor->id
                    ]));
                  }
                }
                // Merchant
                $merchants = User::whereHas('roles', function ($q) {
                  $q->where("name", "Merchant");
                })->with('tags.profile')->get();
                foreach ($merchants as $merchant) {
                  $trail_amount = 0;
                  foreach ($merchant->tags as $tag) {
                    $trail_amount += $tag->profile->today_spent_amount;
                  }
                  $trail_amount = round($trail_amount * $merchant_rate);
                  if($trail_amount > 0) {
                    $merchant->histories()->create([
                      'type' => 'Trail',
                      'creditor' => 'system',
                      'creditor_id' => 1,
                      'message' =>  "Received ".$trail_amount." credits as primary trail.",
                      'old_value' => $merchant->credit,
                      'new_value' => $merchant->credit + $trail_amount,
                      'user_id' => $merchant->id
                    ]);
                    DB::table('users')
                      ->where('id','=',$merchant->id)
                      ->increment('credit', $trail_amount);
                    dispatch(new NotificationJob('trail_notification', (object) [
                      "amount" => $trail_amount,
                      "secondary_trail" => 0,
                      "user_id" => $merchant->id
                    ]));
                    # Secondary trail
                    $mentor = User::where("id","=",$merchant->tag_id)->first();
                    $mentor->histories()->create([
                      'type' => 'Trail',
                      'creditor' => 'system',
                      'creditor_id' => 1,
                      'message' =>  "Received ".round($trail_amount * $mentor_crate)." credits as secondary trail.",
                      'old_value' => $mentor->credit,
                      'new_value' => $mentor->credit + round($trail_amount * $mentor_crate),
                      'user_id' => $mentor->id
                    ]);
                    DB::table('users')
                      ->where('id','=',$mentor->id)
                      ->increment('credit', round($trail_amount * $mentor_crate));
                    dispatch(new NotificationJob('trail_notification', (object) [
                      "amount" => 0,
                      "secondary_trail" => round($trail_amount * $mentor_crate),
                      "user_id" => $mentor->id
                    ]));
                  }
                }
                // Clear
                DB::table('profiles')->update(['today_spent_amount'=>0]);
                break;
              case "favourites":
                $chatrooms = Chatroom::where('user_id','>',1)->get();
                foreach ($chatrooms as $chatroom) {
                  if(!$chatroom->favouritesOf->contains($chatroom->user_id)) {
                    $chatroom->favouritesOf()->attach($chatroom->user_id);
                  }
                }
                break;
              case 'users':
                $users = OnlineUsers::offline()->get();
                foreach ($users as $u) {
                  $user = User::with('level')->find($u->user_id);
                  $now = Carbon::now();
                  $online_since = Carbon::parse($u->created_at);
                  $diff = $now->diffInSeconds($online_since);
                  if($diff > 0) {
                    $update_bar = getLevelBarOfTime($diff, $user->level->value);
                    dispatch(new LevelJob("add bar", $u->user_id, (object) [
                      "amount" => $update_bar
                    ]));
                  }
                  // Active rooms
                  $active_rooms = DB::table('chatroom_users')->where("user_id","=", $u->user_id)->get();
                  foreach ($active_rooms as $room) {
                    Artisan::call("chatmini:chatroom", [
                      'op' => 'leave',
                      '--user' => $u->user_id,
                      '--id' => $room->chatroom_id,
                    ]);
                  }
                  DB::table('chatroom_users')->where("user_id","=", $u->user_id)->delete();
                  // Threads
                  $threads = DB::table('private_public_messages_participants')->select(['thread_id'])->where('user_id','=', $u->user_id)->get();
                  foreach ($threads as $th) {
                    $thread = Thread::find($th->thread_id);
                    if($thread->mode == 'private') {
                      $left_message = $user->username.' is offline';
                    } else {
                      $left_message = $user->username.'['.$user->level->value.'] left this group';
                    }
                    $data = [
                      'sender' => $user,
                      'formatted_text' => $left_message,
                      $u->user_id,
                      'type' => "info"
                    ];
                    event(new NewMessageSent($data, $thread->slug));
                  }
                  DB::table('private_public_messages_participants')->where('user_id','=', $u->user_id)->delete();
                  // Delete online users
                  $u->delete();
                }
                break;
              case 'merchants':
                $role = Role::findByName('Merchant');
                $now = Carbon::now();
                $users = $role->users()->where('program_expiry','<=', $now)->get();
                foreach ($users as $user) {
                  if($user->program_point >= getProgramPointLimit(['Merchant'])) {
                    dispatch(new NotificationJob("color_renew_successful",(object) [
                      "role" => "Merchant",
                      "user_id" => $user->id,
                      "username" => $user->username,
                      "tag_id" => $user->tag_id,
                    ]));

                    $user->program_point = 0;
                    $user->program_expiry = Carbon::now()->addDays(30);
                    $user->save();
                  } else {
                    dispatch(new NotificationJob("color_renew_not_done",(object)[
                      "role" => "Merchant",
                      "user_id" => $user->id,
                    ]));
                    $user->program_point = 0;
                    $user->program_expiry = NULL;
                    $user->alltags()->delete();
                    DB::table('users')->where('tag_id','=',$user->id)->update([
                      'tag_id' => 1
                    ]);
                    Artisan::call("chatmini:user", [
                      'op' => 'update',
                      '--user' => $user->id,
                      '--roles' => "User",
                    ]);
                    $user->tag_id = 1;
                    $user->save();
                  }
                }
                break;
              case 'mentors':
                $role = Role::findByName('Mentor');
                $now = Carbon::now();
                $users = $role->users()->where('program_expiry','<=', $now)->get();
                foreach ($users as $user) {
                  if($user->program_point >= getProgramPointLimit(['Merchant'])) {
                    dispatch(new NotificationJob("color_renew_successful",(object) [
                      "role" => "Mentor",
                      "user_id" => $user->id,
                      "username" => $user->username,
                      "tag_id" => $user->tag_id,
                    ]));

                    $user->program_point = 0;
                    $user->program_expiry = Carbon::now()->addDays(30);
                    $user->save();
                  } else {
                    dispatch(new NotificationJob("color_renew_not_done",(object)[
                      "role" => "Mentor",
                      "user_id" => $user->id,
                    ]));
                    $user->program_point = 0;
                    $user->program_expiry = NULL;
                    $user->tag_id = 1;
                    $user->alltags()->delete();
                    DB::table('users')->where('tag_id','=',$user->id)->update([
                      'tag_id' => 1
                    ]);
                    Artisan::call("chatmini:user", [
                      'op' => 'update',
                      '--user' => $user->id,
                      '--roles' => "User",
                    ]);
                    $user->save();
                  }
                }
                break;
              case 'tags':
                $now = Carbon::now();
                $tags = MerchantTag::where('expire_at','<=', $now)->get();
                foreach ($tags as $tag) {
                  DB::table('users')->where('id','=',$tag->user_id)->update([
                    'tag_id' => 1
                  ]);
                  $user = DB::table('users')->select(['username','id'])->where('id','=',$tag->user_of)->first();
                  $me = DB::table('users')->select(['username','id'])->where('id','=',$tag->user_id)->first();
                  dispatch(new NotificationJob("tag_expired",(object)[
                    "under_of" => $user,
                    "me" => $me,
                  ]));
                  $tag->delete();
                }
                break;
            }
            break;
        }
    }
    protected function getArguments() {
        return [
        ];
    }
    protected function getOptions() {
        return [
        ];
    }
}
