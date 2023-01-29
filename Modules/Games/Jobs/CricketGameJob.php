<?php

namespace Modules\Games\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Games\Entities\Game;
use Modules\Games\Entities\GameParticipants;
use Modules\Games\Entities\Leaderboard;
use Modules\Games\Events\SendGameMessage;
use Modules\Level\Jobs\LevelJob;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\User;
use function GuzzleHttp\Psr7\str;

class CricketGameJob implements ShouldQueue {
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
        if(($this->model->id == 469 || $this->model->id == 470) && $amount < 100) {
          event(new InfoCommand("Cannot start game. There is a running contest in this room. Only 100 credit game can be started.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        if(($this->model->id == 469 || $this->model->id == 470) && $amount > 100) {
          event(new InfoCommand("Cannot start game. There is a running contest in this room. Only 100 credit game can be started.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        $this->extra_info->amount = $amount;
        # all good?
        $uuid = uniqid();
        $game_key = 'cricket_'.$this->type.'_'.$this->model->id;
        $game_info = \cache($game_key,[]);
        // Check if game already started
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
          'type' => 'Cricket Game',
          'creditor' => $this->type,
          'creditor_id' => $this->model->id,
          'message' => "Started cricket bot for ".$amount.' credits. Game ID: #'.$uuid,
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
        $game->save(); // phase updated
        dispatch(new CricketGameJob("setup game",$this->type, $this->model, $this->user))->delay(now()->addSeconds(30))->onQueue('game');
        dispatch(new CricketGameJob("check multi start", $game_key, $this->model, $this->user))->delay(now()->addSeconds(10))->onQueue('game');
        $game->participants()->create([
          'round' => 0,
          'username' => $this->user->username,
          'score' => 0,
          'raw_score' => -2,
          'extra_info' => [
            'total_score' => 0
          ]
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
              'type' => 'Cricket game',
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
          event(new SendGameMessage($joined_game->type, $game->owner_id, "Cricket Bot", 'Multiple bot started. Refunded all deducted credits. Sorry for inconvenience.', (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
          dispatch(new NotificationJob('admin_info_notification',(object)[
            'message' => 'Auto refund successfully done for ID: #'.$joined_game->uuid.'. Amount: '.$amount.'. Check panel for more info.',
            'title' => 'Alert!!'
          ]));
          $game->action_man = 'swftea';
          $game->round = 0;
          $game->save();
          $game->participants()->delete();
          $game->delete();
          array_shift($games);
          foreach ($games as $game) {
            $user = User::where("username","=",$game->started_by)->first();
            $amount = $game->amount;
            $user->histories()->create([
              'type' => 'Cricket game',
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
            dispatch(new NotificationJob('admin_info_notification',(object) [
              'message' => 'Auto refund successfully done for ID: #'.$game->uuid.'. Amount: '.$amount.'. Check panel for more info.',
              'title' => 'Alert!!'
            ]));
          }
        }
        break;
      case 'join game':
        $bot = 'Cricket Bot';
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
          'type' => 'Cricket game',
          'creditor' => $this->type,
          'creditor_id' => $this->model->id,
          'message' => "Joined cricket bot for ".$amount.' credits. ID: #'.$game->game_id,
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
          'raw_score' => -2,
          'extra_info' => []
        ]);
        $game->total_participants += 1;
        $game->save();
        $message = $this->user->username.' joined the game.';
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
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
            'type' => 'Cricket Game',
            'creditor' => $this->type,
            'creditor_id' => $this->model->id,
            'message' => "Refunded ".$amount.' credits from Cricket game.',
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
          $bot = 'Cricket Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
          $game->participants()->delete();
          dispatch(new CricketGameJob("send game start message", $this->type, $this->model, $this->user))->delay(now()->addSeconds(1))->onQueue('game');
          return;
        } // not enough participants
        # multi game
        $game->phase = 2;
        $game->participants()->update([
          'round' => 1,
          'score' => 0,
          'raw_score' => -2,
        ]);
        $game->save();
        foreach ($game->participants()->get() as $participant) {
          $user_par = DB::table('users')->select(['id'])->where('username','=', $participant->username)->first();
          $bar = getBarForCredit($user_par->id, $game->amount);
          dispatch(new LevelJob('add bar', $user_par->id, (object)[
            'amount' => $bar,
            'credit' => $game->amount
          ]));
          dispatch(
            new SeasonPoint(
              'add points',
              $user_par->id,
              'Cricket',
              'play_game',
              1)
          )->onQueue('low');
        }
        dispatch(new CricketGameJob("start round", $this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('game');
        break;
      case 'start round':
        $game = Game::where('id','=', $this->model->game->id)->first();
        $old_round = $game->round;
        $game->round = $old_round + 1;
        $game->phase = 3;
        $game->save();
        $game->participants()->update([
          'raw_score' => '-2',
          'round' => $game->round
        ]);
        $message = 'Round #'.$game->round.'. Players !d to bat [20 seconds]';
        $bot = 'Cricket Bot';
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        dispatch(new CricketGameJob("lock game", $this->type, $this->model, $this->user))->delay(now()->addSeconds(18))->onQueue('game');
        break;
      case 'draw round':
        if(!$this->model->game()->exists()) {
          event(new InfoCommand("Game not running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
          return;
        }
        $game = Game::where('id','=', $this->model->game->id)->with(['participants'])->first();
        $bot = 'Cricket Bot';
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
          event(new InfoCommand("Please wait some time to flip bat.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        # not in round
        $p_round = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->first();
        if($p_round->round != $game->round) {
          event(new InfoCommand("You are not in this round.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        # already drawn
        $has_drawn = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->where('raw_score','!=','-2')->count();
        if($has_drawn) {
          event(new InfoCommand("You have already flipped for this round.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        # all good
        # participant
        $participant = GameParticipants::where('game_id','=', $game->id)->where('username','=',$this->user->username)->first();
        if(!$participant) {
          event(new InfoCommand("You are not in this round.", $this->user->id, $this->type, $this->model->id));
          return;
        }
        $rand = randomCricketScore();
        $run = getCricketRun($rand);
        if($run == 6) {
          dispatch(
            new SeasonPoint(
              'add points',
              $this->user->id,
              'Cricket',
              'hit_six',
              1)
          )->onQueue('low');
          if($this->model->id == 469 || $this->model->id == 470) {
            Leaderboard::create([
              'username' => $this->user->username,
              'type' => 'cricket_six',
            ]);
          }
        }
        if($run == 4) {
          dispatch(
            new SeasonPoint(
              'add points',
              $this->user->id,
              'Cricket',
              'hit_four',
              1)
          )->onQueue('low');
        }
        if($run == 1) {
          dispatch(
            new SeasonPoint(
              'add points',
              $this->user->id,
              'Cricket',
              'hit_single',
              1)
          )->onQueue('low');
        }
        $message = getHitLabel($participant->username, $rand);
        $participant->score += (int) $run < 0 ? 0 : (int) $run;
        $participant->raw_score = (string) $run;
        $participant->save();
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message), (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]);
        break;
      case 'finalize round':
        $game = Game::where('id','=', $this->model->game->id)->first();
        $all_participants = $game->participants()->orderBy('score','DESC')->get();
        $participants = [];
        $tied_users = collect([]);
        $high_score = -2;
        $round_skipped = false;
        $out_participants = collect([]);
        foreach ($all_participants as $participant) {
          if($high_score == -2) {
            $high_score = $participant->score;
          }
          // For equal run
          if($high_score == $participant->score) {
            $tied_users->push($participant);
          }
        }
        if(count($tied_users) > 1 && $game->round > 5) {
          $round_skipped = true;
        }

        if($round_skipped) {
          // Delete all unwanted partipants
          $outs = $game->participants()->get();
          foreach ($outs as $out) {
            if($out->score < $high_score) {
              $out_participants->push($out);
            } else {
              $participants[$out->username] = $out->score;
            }
          }
        } else {
          // Delete all unwanted partipants
          $outs = $game->participants()->get();
          foreach ($outs as $out) {
            if ($game->round > 5) {
              if($out->raw_score == '-1') {
                $out_participants->push($out);
              }
              else if($out->score != $high_score) {
                $out_participants->push($out);
              }else {
                $participants[$out->username] = $out->score;
              }
            }
            else if($out->raw_score == '-1') {
              $out_participants->push($out);
            }
             else {
              $participants[$out->username] = $out->score;
            }
          }
        }
        if(count($all_participants) == count($out_participants)) {
          $round_skipped = true;
          $participants = [];
          foreach ($all_participants as $participant) {
            $participants[$participant->username] = $participant->username;
            $participant->raw_score = 0;
            $participant->save();
          }
        } else {
          foreach ($out_participants as $out_participant) {
            $out_participant->delete();
          }
        }
        if(count($participants) == 1) {
          dispatch(new CricketGameJob("game completed", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(1));
        } else {
          dispatch(new CricketGameJob("game results", $this->type, $this->model, $this->user, (object) [
            'participants' => $participants,
            'round_skipped' => $round_skipped
          ]))->onQueue('game')->delay(now()->addSeconds(1));
        }
        break;
      case 'game results done':
        $game = Game::where('id','=', $this->model->game->id)->first();
        if($this->extra_info->round_skipped) {
          $players = array_keys($this->extra_info->participants);
          $message = 'Tied players ('.count($players).'): '.implode(", ", $players);
          event(new SendGameMessage($this->type, $game->owner_id, "Cricked Bot", $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
          $message = 'Tied players bat again, next round in 5 seconds.';
          event(new SendGameMessage($game->type, $game->owner_id, "Cricket Bot", $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        } else {
          $players = array_keys($this->extra_info->participants);
          $message = 'Players are ('.count($players).'): '.implode(", ", $players);
          event(new SendGameMessage($game->type, $game->owner_id, "Cricket Bot", $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
          $message = 'All players next round in 5 second.';
          event(new SendGameMessage($game->type, $game->owner_id, "Cricket Bot", $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        }
        dispatch(new CricketGameJob("start round", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(5));
        break;
      case 'game results':
        $game = Game::where('id','=', $this->model->game->id)->first();
        $bot = 'Cricket Bot';
        $all_participants = $game->participants()->orderBy('score','DESC')->get();
        $message = 'Round is over. Counting runs...';
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        foreach ($all_participants as $participant) {
          $message = $participant->username. ' ('.$participant->score.' runs)';
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        }
        dispatch(new CricketGameJob("game results done", $this->type, $this->model, $this->user, $this->extra_info))->onQueue('game')->delay(now()->addSeconds(1));
        break;
      case 'game error detected':
        $game = Game::where('id','=', $this->model->game->id)->first();
        dispatch(new NotificationJob('admin_info_notification',(object)[
          'message' => 'Error on game Cricket. ID: #'.$game->game_id,
          'title' => 'Alert!!'
        ]));
        event(new SendGameMessage($game->type, $game->owner_id, "Cricket Bot", 'Incident reported to admin.', (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        $game->action_man = 'swftea';
        $game->save();
        $game->participants()->delete();
        $game->delete();
        break;
      case 'game completed':
        $game = Game::where('id','=', $this->model->game->id)->first();
        $bot = 'Cricket Bot';
        $winner_p = GameParticipants::where('game_id','=', $game->id)->first();
        if(!$winner_p) {
          $message = 'Some error occured. Please consult developer!!';
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
          return;
        }
        $winner = User::where('username','=', $winner_p->username)->first();
        $winning_amount = round(($game->amount * $game->total_participants) * 0.9, 2);
        $message = 'Cricket game over! (#'.$game->game_id.')'.$winner->username.' wins credits '.$winning_amount.'! CONGRATS!!';
//        if($this->model->id == 469 || $this->model->id == 470) {
//          Leaderboard::create([
//            'username' => $winner->username,
//            'type' => 'contest_2_2_cricket',
//          ]);
//        }
        dispatch(
          new SeasonPoint(
            'add points',
            $winner->id,
            'Cricket',
            'win_game',
            1)
        )->onQueue('low');
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        $winner->histories()->create([
          'type' => 'Cricket Game',
          'creditor' => $this->type,
          'creditor_id' => $this->model->id,
          'message' => "Won ".$winning_amount.' credits from Cricket game. Game ID: #'.$game->game_id,
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
          'type' => 'cricket',
        ]);

        dispatch(new CricketGameJob("send game start message", $this->type, $this->model, $this->user))->onQueue('game')->delay(now()->addSeconds(1));

        break;
      case 'send game start message':
        $message = "Play Cricket. Type !start to start a new game, !start < amount > for custom entry.";
        $bot = 'Cricket Bot';
        event(new SendGameMessage($this->model->game->type, $this->model->game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        break;
      case 'draw left players':
        $game = Game::where('id','=', $this->model->game->id)->first();
        $bot = 'Cricket Bot';
        $participants_not_drawn = $game->participants()->where('raw_score','=', '-2')->get();
        foreach ($participants_not_drawn as $participants_not_d) {
          $rand = randomCricketScore();
          $run = getCricketRun($rand);
          $message = getHitLabel($participants_not_d->username, $rand);
          $participants_not_d->score += (int) $run < 0 ? 0 : (int) $run;
          $participants_not_d->raw_score = (string) $run;
          $participants_not_d->save();
          $user_for_id = DB::table('users')->select(['id'])->where('username','=',$participants_not_d->username)->first();
          if($run == 6) {
            dispatch(
              new SeasonPoint(
                'add points',
                $user_for_id->id,
                'Cricket',
                'hit_six',
                1)
            )->onQueue('low');
            if($this->model->id == 469 || $this->model->id == 470) {
              Leaderboard::create([
                'username' => $participants_not_d->username,
                'type' => 'cricket_six',
              ]);
            }
          }
          if($run == 4) {
            dispatch(
              new SeasonPoint(
                'add points',
                $user_for_id->id,
                'Cricket',
                'hit_four',
                1)
            )->onQueue('low');
          }
          if($run == 1) {
            dispatch(
              new SeasonPoint(
                'add points',
                $user_for_id->id,
                'Cricket',
                'hit_single',
                1)
            )->onQueue('low');
          }
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        } // Bot draw
        dispatch(new CricketGameJob("finalize round", $this->type, $this->model, $this->user))->delay(now()->addSeconds(1))->onQueue('game');
        break;
      case 'lock game':
        $game = DB::table('games')->where('id','=',$this->model->game->id)->first();
        DB::table('games')->where('id','=',$this->model->game->id)->update([
          'phase' => 4
        ]);
        $message = 'Times up! Tallying...';
        $bot = 'Cricket Bot';
        event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
          'game' => 'cricket',
          'img_width' => 16,
          'img_height' => 16,
        ]));
        dispatch(new CricketGameJob("draw left players", $this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('game');
        break;
      case 'set bot':
        $id = $this->model->id;
        if($this->model->game()->exists()) {
          event(new InfoCommand("Bot cannot be set. Bot is already running.",$this->user->id,$this->type, $id));
        } else {
          $game = new Game();
          $game->owner_id = $id;
          $game->game = 'cricket';
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
