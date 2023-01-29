<?php

namespace Modules\Games\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\AccountHistory\Entities\AccountHistory;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Chatroom\Entities\Chatroom;
use Modules\Games\Entities\Game;
use Modules\Games\Entities\GameParticipants;
use Modules\Games\Entities\Leaderboard;
use Modules\Games\Events\SendGameMessage;
use Modules\Level\Jobs\LevelJob;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\User;

class GameJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $action;
    public $model;
    public $type;
    public $user;
    public $extra_info;
    public function __construct($action, $type, $model, $user, $additional = null) {
        $this->action = $action;
        $this->model = $model;
        $this->type = $type;
        $this->user = $user;
        $this->extra_info = $additional;
    }
    public function handle() {
        switch ($this->action) {
          case 'start game':
            if(!$this->model->game()->exists()) {
              event(new InfoCommand("Cannot start game. Bot is not added to this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $can_not_start_game = DB::table('games')
            ->where('owner_id','=', $this->model->id)
            ->where('phase','!=', 0)
            ->where('type','=', $this->type)->count();
            if($can_not_start_game) {
              event(new InfoCommand("Cannot start game. Bot is already running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $game = Game::where('id','=', $this->model->game->id)->first();
            if(!$game) {
              event(new InfoCommand("Cannot start game. Bot is not added to this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            if(!$game->phase == 0) {
              event(new InfoCommand("Cannot start game. Bot is already running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $amount = $this->extra_info->amount;
            if(!is_numeric($amount)) {
              event(new InfoCommand("Cannot start game. Invalid amount.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            if($this->user->credit < $amount) {
              event(new InfoCommand("Cannot start game. You don't have sufficient credit in your account.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $new_user_credit = $this->user->credit - $amount;
            if($new_user_credit < config('usersystem.minimum_credit_required_to_transfer')) {
              event(new InfoCommand("You must leave ".config('usersystem.minimum_credit_required_to_transfer').' credits on your account.', $this->user->id, $this->type, $this->model->id));
              return;
            }
            if($amount < \cache('min_bot_amount_'.$this->model->id, 10.00)) {
              event(new InfoCommand("Cannot start game. Minimum bot amount is ".\cache('min_bot_amount_'.$this->model->id, 10.00)." credits", $this->user->id, $this->type, $this->model->id));
              return;
            }
            if($amount > \cache('max_bot_amount_'.$this->model->id, 1000000000.00)) {
              event(new InfoCommand("Cannot start game. Maximum bot amount is ".\cache('max_bot_amount_'.$this->model->id, 1000000000.00)." credits", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $this->extra_info->amount = $amount;
            # all good?
            $uuid = uniqid();
            $game_key = 'lowcard_'.$this->type.'_'.$this->model->id;
            $game_info = \cache($game_key,[]);
            if(count($game_info) > 0) {
              event(new InfoCommand("Cannot start game. Bot is already running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $new_game_info = (object) [
              'uuid' => $uuid,
              'started_by' => $this->user->username,
              'amount' => $amount,
              'type' => $this->type,
              'game_id' => $this->model->game->id
            ];
            $game_info[] = $new_game_info;
            \cache([$game_key => $game_info], now()->addSeconds(15));
            $this->user->histories()->create([
              'type' => 'Lowcard Game',
              'creditor' => $this->type,
              'creditor_id' => $this->model->id,
              'message' => "Started lowcard bot for ".$amount.' credits. Game ID: #'.$uuid,
              'old_value' => $this->user->credit,
              'new_value' => $this->user->credit - $amount,
              'user_id' => $this->user->id
            ]);
            DB::table('users')
              ->where('id','=',$this->user->id)
              ->decrement('credit', $amount);
            $game->phase = 1;
            $game->game_id = $uuid;
            $game->started_by = $this->user->id;
            $game->action_man = $this->user->username;
            $game->amount = $this->extra_info->amount;
            $game->total_participants = 1;
            \cache([
              $uuid => 1
            ], now()->addDays(99));
            $game->save(); // phase updated
            dispatch(new GameJob("setup game",$this->type, $this->model, $this->user))->delay(now()->addSeconds(30))->onQueue('game');
            dispatch(new GameJob("check multi start", $game_key, $this->model, $this->user))->delay(now()->addSeconds(10))->onQueue('game');
            $game->participants()->create([
              'round' => 0,
              'username' => $this->user->username,
              'score' => 0,
              'extra_info' => []
            ]);
            break;
          case "check multi start":
            $games = \cache($this->type, []);
            $is_locked = \cache($this->type.'_lock', false);
            if(count($games) > 1 && !$is_locked) {
              \cache([$this->type.'_lock' => true], now()->addSeconds(10));
              $joined_game = $games[0];
              $game = Game::where('id','=', $joined_game->game_id)->with('participants')->first();
              if(!$game) {
                return;
              }
              $amount = $joined_game->amount;
              foreach ($game->participants as $participant) {
                $user_1 = User::where('username','=',$participant->username)->first();
                $user_1->histories()->create([
                  'type' => 'Lowcard game',
                  'creditor' => $joined_game->type,
                  'creditor_id' => $this->model->id,
                  'message' => "Refunded ".$amount.' credits on bot error. ID: #'.$joined_game->uuid,
                  'old_value' => $user_1->credit,
                  'new_value' => $user_1->credit + $amount,
                  'user_id' => $user_1->id
                ]);
                DB::table('users')
                  ->where('id','=',$user_1->id)
                  ->increment('credit', $amount);
              }
              event(new SendGameMessage($joined_game->type, $game->owner_id, "LowCard Bot", 'Multiple bot started. Refunded all deducted credits. Sorry for inconvenience.', (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              dispatch(new NotificationJob('admin_info_notification',(object)[
                'message' => 'Auto refund successfully done for ID: #'.$joined_game->uuid.'. Amount: '.$amount.'. Check panel for more info.',
                'title' => 'Alert!!'
              ]));
              $game->action_man = 'swftea';
              $game->save();
              $game->participants()->delete();
              $game->delete();
              array_shift($games);
              foreach ($games as $game) {
                $user = User::where("username","=",$game->started_by)->first();
                $amount = $game->amount;
                $user->histories()->create([
                  'type' => 'Lowcard game',
                  'creditor' => 'system',
                  'creditor_id' => 1,
                  'message' => "Refunded ".$amount.' credits on bot error. ID: #'.$game->uuid,
                  'old_value' => $user->credit,
                  'new_value' => $user->credit + $amount,
                  'user_id' => $user->id
                ]);
                DB::table('users')
                  ->where('id','=',$user->id)
                  ->increment('credit', $amount);
                dispatch(new NotificationJob('admin_info_notification',(object)[
                  'message' => 'Auto refund successfully done for ID: #'.$game->uuid.'. Amount: '.$amount.'. Check panel for more info.',
                  'title' => 'Alert!!'
                ]));
              }
            }
            break;
          case 'join game':
            $bot = 'Lowcard Bot';
            if(!$this->model->game()->exists()) {
              event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $game = Game::where('id','=', $this->model->game->id)->first();
            if(!$game) {
              event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
              return;
            }
            # in game
            $in_game = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->count();
            if($in_game) {
              event(new InfoCommand("You are already in the game.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            if($game->phase != 1) {
              event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $amount = $game->amount;
            if($this->user->credit < $amount) {
              event(new InfoCommand("Cannot join game. You don't have sufficient credit in your account", $this->user->id, $this->type, $this->model->id));
              return;
            }

            # all good?
            $this->user->histories()->create([
              'type' => 'Lowcard game',
              'creditor' => $this->type,
              'creditor_id' => $this->model->id,
              'message' => "Joined lowcard bot for ".$amount.' credits. ID: #'.$game->game_id,
              'old_value' => $this->user->credit,
              'new_value' => $this->user->credit - $amount,
              'user_id' => $this->user->id
            ]);
            DB::table('users')
              ->where('id','=',$this->user->id)
              ->decrement('credit', $amount);

            $game->participants()->create([
              'round' => 0,
              'username' => $this->user->username,
              'score' => 0,
              'extra_info' => []
            ]);
            $game->total_participants += 1;
            $game->save();
            \cache([
              $game->game_id => \cache($game->game_id, 0) + 1
            ], now()->addDays(99));
            $message = $this->user->username.' joined the game.';
            event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            break;
          case 'setup game':
            if(!$this->model->game()->exists()) {
              event(new InfoCommand("Internal server error. Errcode: 1", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $game = Game::where('id','=', $this->model->game->id)->first();
            if(!$game) {
              return;
            }
            if($game->participants()->count() < 2) {
              $amount = $game->amount;
              $fresh_user = DB::table('users')->select(['credit','username'])->where('id','=', $this->user->id)->first();
              $this->user->histories()->create([
                'type' => 'Lowcard Game',
                'creditor' => $this->type,
                'creditor_id' => $this->model->id,
                'message' => "Refunded ".$amount.' credits from LowCard game.',
                'old_value' => $fresh_user->credit,
                'new_value' => $fresh_user->credit + $amount,
                'user_id' => $this->user->id
              ]);
              DB::table('users')
                ->where('id','=',$this->user->id)
                ->increment('credit', $amount);

              $game->phase = 0;
              $game->action_man = '';
              $this->started_by = 0;
              $this->amount = 0.0;
              $game->save();

              $message = 'Joining ends. Not enough players. Need 2 ';
              $bot = 'Lowcard Bot';
              event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              $game->participants()->delete();
              \cache([
                'game_'.$this->type.'_'.$this->model->id.'_total_participants' => 0
              ], now()->addDays(99));
              dispatch(new GameJob("send game start message", $this->type, $this->model, $this->user))->delay(now()->addSeconds(1))->onQueue('game');
              return;
            } // not enough participants
            # multi game
            $game->phase = 2;
            $game->participants()->update([
              'round' => 1,
              'score' => 0
            ]);
            $game->save();
            // Add level bar
            foreach ($game->participants()->get() as $participant) {
              $user_par = DB::table('users')
                ->select(['id'])
                ->where('username','=', $participant->username)
                ->first();
              $bar = getBarForCredit($user_par->id, $game->amount);
              dispatch(new LevelJob('add bar', $user_par->id, (object)[
                'amount' => $bar,
                'credit' => $game->amount
              ]));
              dispatch(
                new SeasonPoint(
                  'add points',
                  $user_par->id,
                  'Lowcard',
                  'play_game',
                  1)
              )->onQueue('low');
            }
            dispatch(new GameJob("start round", $this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('game');
            break;
          case 'start round':
            $game = Game::where('id','=', $this->model->game->id)->first();
            $old_round = $game->round;
            $game->round = $old_round + 1;
            $game->phase = 3;
            $game->save();
            $game->participants()->where('round','=',$game->round)->update([
              'score' => 0
            ]);
            $message = 'Round #'.$game->round.'. Players !d to draw [20 seconds]';
            $bot = 'Lowcard Bot';
            event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            dispatch(new GameJob("lock game", $this->type, $this->model, $this->user))->delay(now()->addSeconds(18))->onQueue('game');
            break;
          case 'draw round':
            if(!$this->model->game()->exists()) {
              event(new InfoCommand("Game not running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $game = Game::where('id','=', $this->model->game->id)->with(['participants'])->first();
            $bot = 'Lowcard Bot';
            if(!$game) {
              event(new InfoCommand("Game not running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
              return;
            }
            $in_game = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->get()->count();
            if(!$in_game) {
              event(new InfoCommand("You are not in this game.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            if($game->phase != 3) {
              event(new InfoCommand("Please wait some time to draw cards.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            # not in round
            $p_round = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->first();
            if($p_round->round != $game->round) {
              event(new InfoCommand("You are not in this round.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            # already drawn
            $has_drawn = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->where('score','>',0)->count();
            if($has_drawn) {
              event(new InfoCommand("You have already drawn for this round.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            # all good
            # participant
            $participant = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->first();
            if(!$participant) {
              event(new InfoCommand("You are not in this round.", $this->user->id, $this->type, $this->model->id));
              return;
            }
            $rand = rand(1, 13);
            $participant->score = $rand;
            $participant->raw_score = lowcardRawScore($rand);
            if($rand == 13) {
              dispatch(
                new SeasonPoint(
                  'add points',
                  $this->user->id,
                  'Lowcard',
                  'get_any_a',
                  1)
              )->onQueue('low');
            }
            if($rand == 12) {
              dispatch(
                new SeasonPoint(
                  'add points',
                  $this->user->id,
                  'Lowcard',
                  'get_any_k',
                  1)
              )->onQueue('low');
            }
            $participant->save();
            $message = $participant->username.' draws : '.$participant->raw_score.' '.lowcardCardImage($participant->raw_score);
            event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            break;
          case 'finalize round':
            $game = Game::where('id','=', $this->model->game->id)->first();
            $bot = 'Lowcard Bot';
            $participants = $game->participants()->orderBy('score','ASC')->get();
            $low_scorers = collect([]);
            $low_score = 1000;
            $total_participants = count($participants);
            foreach ($participants as $participant) {
              if($participant->score <= $low_score) {
                $low_score = $participant->score;
                $low_scorers->push($participant);
                continue;
              } else {
                $participant->score = 100;
                $participant->save();
              }
            }
            $round_skipped = false;
            if(count($low_scorers) == 1) {
              $low_scorer = $low_scorers[0];
              $total_participants--;
              $message = $low_scorer->username." out with lowest card ".$low_scorer->raw_score.' '.lowcardCardImage($low_scorer->raw_score).' !';
              event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              $low_scorer->delete();
            }
            else {
              $tied_users = $low_scorers->pluck('username')->toArray();
              $message = 'Tied players ('.count($low_scorers).'): '.implode(", ", $tied_users).'.';
              event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              $message = 'Tied players draw again, next round in 5 seconds.';
              event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              foreach ($low_scorers as $low_scorer) {
                $low_scorer->round = $game->round + 1;
                $low_scorer->save();
              }
              $round_skipped = true;
            }
            if(!$round_skipped) {
              $game->participants()->update([
                'round' => $game->round + 1
              ]);
            }
            # recheck participants
            if($total_participants == 1) { // game ends
              dispatch(new GameJob("game completed", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(1));
            } elseif($total_participants == 0) {
              dispatch(new GameJob("game error detected", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(1));
            } else {
              if(!$round_skipped) {
                $players = $game->participants->pluck('username')->toArray();
                $message = 'Players are ('.count($players).'): '.implode(", ", $players);
                event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                  'game' => 'lowcard',
                  'img_width' => 17.78,
                  'img_height' => 24,
                ]));
                $message = 'All players next round in 5 second.';
                event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                  'game' => 'lowcard',
                  'img_width' => 17.78,
                  'img_height' => 24,
                ]));
              }
              dispatch(new GameJob("start round", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(5));
            }
            break;
          case 'game error detected':
            $game = Game::where('id','=', $this->model->game->id)->first();
            dispatch(new NotificationJob('admin_info_notification',(object)[
              'message' => 'Error on game LowCard. ID: #'.$game->game_id,
              'title' => 'Alert!!'
            ]));
            event(new SendGameMessage($game->type, $game->owner_id, "LowCard Bot", 'Incident reported to admin.', (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            $game->action_man = 'swftea';
            $game->save();
            $game->participants()->delete();
            $game->delete();
            break;
          case 'game completed':
            $game = Game::where('id','=', $this->model->game->id)->first();
            $bot = 'Lowcard Bot';
            $winner_p = GameParticipants::where('game_id','=', $game->id)->first();
            if(!$winner_p) {
              $message = 'Some error occured. Please consult developer!!';
              event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
              return;
            }
            $winner = User::where('username','=', $winner_p->username)->first();
            $total_participants_count = \cache($game->game_id, 0);
            $winning_amount = round(($game->amount * $total_participants_count) * 0.9, 2);
            $message = 'LowCard game over! (#'.$game->game_id.')'.$winner->username.' wins credits '.$winning_amount.'! CONGRATS!!';
            event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            # mission
            dispatch(
              new SeasonPoint(
                'add points',
                $winner->id,
                'Lowcard',
                'win_game',
                1)
            )->onQueue('low');
            $winner->histories()->create([
              'type' => 'Lowcard Game',
              'creditor' => $this->type,
              'creditor_id' => $this->model->id,
              'message' => "Won ".$winning_amount.' credits from LowCard game. Game ID: #'.$game->game_id,
              'old_value' => $winner->credit,
              'new_value' => $winner->credit + $winning_amount,
              'user_id' => $winner->id
            ]);
            DB::table('users')
              ->where('id','=',$winner->id)
              ->increment('credit', $winning_amount);
            $game->round = 0;
            $game->phase = 0;
            $game->total_participants = 0;
            $game->save();
            $game->participants()->delete();

            // Add on leaderboard
            Leaderboard::create([
              'username' => $winner->username,
              'type' => 'lowcard',
            ]);
            if($this->model->id == 45 || $this->model->id == 46) {
              Leaderboard::create([
                'username' => $winner->username,
                'type' => 'contest_2_2_lowcard',
              ]);
            }

            dispatch(new GameJob("send game start message", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(1));

            break;
          case 'send game start message':
            $message = "Play LowCard. Type !start to start a new game, !start < amount > for custom entry.";
            $bot = 'Lowcard Bot';
            event(new SendGameMessage($this->model->game->type, $this->model->game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            break;
          case 'draw left players':
            $game = Game::where('id','=', $this->model->game->id)->first();
            $bot = 'Lowcard Bot';
            $participants_not_drawn = $game->participants()->where('score','=',0)->get();
            foreach ($participants_not_drawn as $participants_not_d) {
              $rand = rand(1, 13);
              $participants_not_d->score = $rand;
              $participants_not_d->raw_score = lowcardRawScore($rand);
              $participants_not_d->save();
              // Get user id of participants not drawn
              $user_for_id = DB::table('users')->select(['id'])->where('username','=',$participants_not_d->username)->first();
              if($rand == 13) {
                dispatch(
                  new SeasonPoint(
                    'add points',
                    $user_for_id->id,
                    'Lowcard',
                    'get_any_a',
                    1)
                )->onQueue('low');
              }
              if($rand == 12) {
                dispatch(
                  new SeasonPoint(
                    'add points',
                    $user_for_id->id,
                    'Lowcard',
                    'get_any_k',
                    1)
                )->onQueue('low');
              }
              $message = 'Bot draws for '.$participants_not_d->username.': '.$participants_not_d->raw_score.' '.lowcardCardImage($participants_not_d->raw_score);
              event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
                'game' => 'lowcard',
                'img_width' => 17.78,
                'img_height' => 24,
              ]));
            } // Bot draw
            dispatch(new GameJob("finalize round", $this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('game');
            break;
          case 'lock game':
            $game = DB::table('games')->where('id','=',$this->model->game->id)->first();
            DB::table('games')->where('id','=',$this->model->game->id)->update([
              'phase' => 4
            ]);
            $message = 'Times up! Tallying cards...';
            $bot = 'Lowcard Bot';
            event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
              'game' => 'lowcard',
              'img_width' => 17.78,
              'img_height' => 24,
            ]));
            dispatch(new GameJob("draw left players", $this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('game');
            break;
          case 'set bot':
            $id = $this->model->id;
            if($this->model->game()->exists()) {
              event(new InfoCommand("Bot cannot be set. Bot is already running.",$this->user->id,$this->type, $id));
            } else {
              $game = new Game();
              $game->owner_id = $id;
              $game->game = 'lowcard';
              $game->game_id = uniqid();
              $game->type = $this->type;
              $game->action_man = $this->user->username;
              $game->save();
            }
            break;
          case 'clear bot':
            $id = $this->model->id;
            $can_stop_bot = false;
            if(!$can_stop_bot && $this->user->can("stop bot in in any room")) {
              $can_stop_bot = true;
            }
            if(!$can_stop_bot && $this->model->game()->exists() && $this->model->game->phase == 0) {
              $can_stop_bot = true;
            }
            if($can_stop_bot) {
              $game = Game::where('owner_id', $this->model->id)->where('type','=',$this->type)->first();
              if($game) {
                $game->action_man = $this->user->username;
                $game->save();
                $game->participants()->delete();
                $game->delete();
              } else {
                event(new InfoCommand("Cannot stop bot.",$this->user->id,$this->type,$id));
              }
            } else {
              event(new InfoCommand("Cannot stop bot.",$this->user->id,$this->type,$id));
            }
            break;
        }
    }
}
