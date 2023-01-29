<?php

namespace Modules\Games\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\AccountHistory\Entities\AccountHistory;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Games\Entities\Game;
use Modules\Games\Entities\GameParticipants;
use Modules\Games\Entities\Leaderboard;
use Modules\Games\Events\SendGameMessage;
use Modules\SwfteaMission\Jobs\SeasonPoint;

class DiceGameJob implements ShouldQueue
{
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
      switch ($this->action) {
        case 'start game':
          if(!$this->model->game()->exists()) {
            event(new InfoCommand("Cannot start game. Bot is not added to this ".$this->type, $this->user->id, $this->type, $this->model->id));
            return;
          }
          // Multi Game
          $can_not_start_game = DB::table('games')
            ->where('owner_id','=', $this->model->id)
            ->where('phase','!=', 0)
            ->where('type','=', $this->type)->count();
          if($can_not_start_game) {
            event(new InfoCommand("Cannot start game. Bot is already running in this ".$this->type, $this->user->id, $this->type, $this->model->id));
            return;
          }
          $dice_key = 'dice_game_phase_'.$this->type.'_'.$this->model->game->id;
          if(cache($dice_key, 0) == 0) {
            cache([$dice_key => 1], now()->addCenturies(60));
          } else {
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
          $game->phase = 1;
          $game->game_id = uniqid();
          $game->started_by = $this->user->id;
          $game->action_man = $this->user->username;
          $game->amount = 0;
          $game->total_participants = 0;
          $game->save(); // phase updated
          dispatch(new DiceGameJob("end betting",$this->type, $this->model, $this->user))->delay(now()->addSeconds(43))->onQueue('high');
          break;
        case 'end betting':
          $game = Game::where('id','=', $this->model->game->id)->first();
          $game->phase = 2;
          $game->save();
          $message = 'Betting ends.';
          $dice_key = 'dice_game_phase_'.$this->type.'_'.$this->model->game->id;
          cache([$dice_key => 2], now()->addCenturies(60));
          $bot = 'Guess Bot';
          event(new SendGameMessage($this->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
          dispatch(new DiceGameJob("finalize start",$this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('high');
          break;
        case 'calculate result':
          $bot = 'Guess Bot';
          $game = Game::where('id','=', $this->model->game->id)->first();
          $message = '';
          $matches = [];
          $dices = shuffleDice();
          $shuffled = $dices['shuffle'];
          $winner_dice = $dices['winning'];
          foreach ($shuffled as $value) {
            $matches[] = getDiceWordFromShortKey($value);
          }
          $message .= ' \n';
          $winner_keys = [];
          foreach ($winner_dice as $key => $count) {
            if($count == 1) {
              continue;
            }
            $winner_keys[] = $key;
            $message .= '- '.diceImage(getDiceWordFromShortKey($key)).' '.getDiceWordFromShortKey($key).': '.$count.'x\n';
          }
          if(count($winner_keys) > 0) {
            $message .= ' \n';
          }
          // winning
          $all_guesses = $game->participants()->get();
          $winners = [];
          $game_losers = [];
          $game_winners = [];
          foreach ($all_guesses as $guess) {
            $guess_key = getDiceKeyFromShortId($guess->score);
            if(in_array($guess_key, $winner_keys)) {
              $game_winners[$guess->username] = $guess->username;
              if(!array_key_exists($guess->username, $winners)) {
                $winners[$guess->username] = [];
              }
              if(!array_key_exists($guess_key, $winners[$guess->username])) {
                $winners[$guess->username][$guess_key] = $guess->extra_info['amount'];
              } else {
                $winners[$guess->username][$guess_key] = $winners[$guess->username][$guess_key] + $guess->extra_info['amount'];
              }
            } else {
              $game_losers[$guess->username] = $guess->username;
            }
          }
          foreach ($game_winners as $game_winner) {
            Leaderboard::create([
              'username' => $game_winner,
              'type' => 'top_game_contest_guess',
            ]);
          }
          $common_users = array_intersect($game_winners, $game_losers);
          if(count($common_users) > 0) {
            foreach ($common_users as $user) {
              unset($game_losers[$user]);
            }
          }

          foreach ($winners as $winner => $value) {
            foreach ($value as $key => $amount) {
              $get_x_of_dice = $winner_dice[$key];
              $winning_amount = round((($get_x_of_dice * $amount) + $amount) * 0.9,2);
              $user = DB::table('users')->select(['credit','id'])->where('username','=', $winner)->first();
              AccountHistory::create([
                'type' => 'Guess Game',
                'creditor' => $this->type,
                'creditor_id' => $game->id,
                'message' => 'Won '.$winning_amount.' credits from Guess game for guessing on '.getDiceWordFromShortKey($key).'. ['.diceGameId($game->game_id).']',
                'old_value' => $user->credit,
                'new_value' => $user->credit + $winning_amount,
                'user_id' => $user->id
              ]);
              DB::table('users')
                ->where('id','=',$user->id)
                ->increment('credit', $winning_amount);
              // Add on leaderboard
              Leaderboard::create([
                'username' => $winner,
                'type' => 'guess',
              ]);
              dispatch(
                new SeasonPoint(
                  'add points',
                  $user->id,
                  'Guess',
                  'win_game',
                  1)
              )->onQueue('low');
              // Add message
              $message .= '-'.$winner.' has won '.number_format($winning_amount,2,'.','').' credits for placing '.number_format($amount,2,'.','').' credits on '.getDiceWordFromShortKey($key).' \n';
            }
          }

          if(count($winners) == 0) {
            $message .= 'No one wins. Better luck next time.';
          }

          $game->total_participants = 0;
          $game->participants()->delete();
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, [
            'roll' => $matches,
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
          $game->phase = 0;
          $game->save();
          $message = 'Play Guess. Type !start to start a new round.';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'dice',
            'isWinningMessage' => true,
            'winners' => $game_winners,
            'losers' => $game_losers,
            'img_width' => 18,
            'img_height' => 18,
          ]));
          $dice_key = 'dice_game_phase_'.$this->type.'_'.$this->model->game->id;
          cache([$dice_key => 0], now()->addCenturies(60));
          break;
        case 'do bet':
          $bot = 'Guess Bot';
          if(!$this->model->game()->exists()) {
            event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
            return;
          }
          $game = Game::where('id','=', $this->model->game->id)->first();
          if(!$game) {
            event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
            return;
          }
          if($game->phase == 0) {
            event(new InfoCommand("Game has not started. Type !start for a new game.", $this->user->id, $this->type, $this->model->id));
            return;
          }
          $dice_key = 'dice_game_phase_'.$this->type.'_'.$this->model->game->id;
          if(cache($dice_key, 0) != 1) {
            event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
            return;
          }
          if(!$game->phase == 1) {
            event(new InfoCommand("Sorry! the round is already over. Type !start for new game", $this->user->id, $this->type, $this->model->id));
            return;
          }
//          $can_bet = DB::table('game_participants')->where('game_id','=', $game->id)->where('username','=',$this->user->username)->count();
//          if($can_bet > 2) {
//            event(new InfoCommand("You cannot bet more than 2 times.", $this->user->id, $this->type, $this->model->id));
//            return;
//          }
          $bet_on = $this->extra_info->bet_on;
          if(!getDiceWordFromShortKey($bet_on)) {
            event(new InfoCommand("Invalid bet group.", $this->user->id, $this->type, $this->model->id));
            return;
          }
          $amount = $this->extra_info->amount;
          if(!is_numeric($amount)) {
            event(new InfoCommand("Invalid bet amount.", $this->user->id, $this->type, $this->model->id));
            return;
          }
          if($amount < \cache('min_bot_amount_'.$this->model->id, 10.00)) {
            event(new InfoCommand("Bet amount must be at least ".\cache('min_bot_amount_'.$this->model->id, 10.00)." credits", $this->user->id, $this->type, $this->model->id));
            return;
          }
//          if($amount < 200) {
//            event(new InfoCommand("Bet amount must be at least 200.00 credits.", $this->user->id, $this->type, $this->model->id));
//            return;
//          }
          if($amount > \cache('max_bot_amount_'.$this->model->id, 20000.00)) {
            event(new InfoCommand("Bet amount cannot be more than ".\cache('max_bot_amount_'.$this->model->id, 20000.00)." credits", $this->user->id, $this->type, $this->model->id));
            return;
          }
          if($this->user->credit < $amount) {
            event(new InfoCommand("You don't have sufficient credit in your account.", $this->user->id, $this->type, $this->model->id));
            return;
          }
          if(($this->user->credit - $amount) < config('usersystem.minimum_credit_required_to_transfer')) {
            event(new InfoCommand("You must leave " . config('usersystem.minimum_credit_required_to_transfer') . " credits in your account.", $this->user->id, $this->type, $this->model->id));
            return;
          }
//          $my_guesses = $game->participants()->where('username','=', $this->user->username)->get();
//          $my_guesses = DB::table('game_participants')
//            ->where('game_id','=', $game->id)
//            ->where('username','=',$this->user->username)
//            ->get();
//          $guesses = [];
//          foreach ($my_guesses as $my_guess) {
//            $key = getDiceKeyFromShortId($my_guess->score);
//            if(!$key) {
//              return;
//            }
//            if(array_key_exists(getDiceWordFromShortKey($key), $guesses)) {
//              $guesses[getDiceWordFromShortKey($key)] = $guesses[getDiceWordFromShortKey($key)] + $my_guess->extra_info['amount'];
//            } else {
//              $guesses[getDiceWordFromShortKey($key)] = $my_guess->extra_info['amount'];
//            }
//          }
//          if(count($guesses) >= 2) {
//            event(new InfoCommand("You cannot bet in more than 2 groups.", $this->user->id, $this->type, $this->model->id));
//            return;
//          }
          $bet_on_word = getDiceWordFromShortKey($this->extra_info->bet_on);
          $bet_on_key = getDiceIdFromShortKey($this->extra_info->bet_on);
          # all good?
          $this->user->histories()->create([
            'type' => 'Guess Game',
            'creditor' => $this->type,
            'creditor_id' => $game->id,
            'message' => 'Placed '.number_format($amount,2,'.','').' credits on '.$bet_on_word.' for guess game. ['.diceGameId($game->game_id).']',
            'old_value' => $this->user->credit,
            'new_value' => $this->user->credit - $amount,
            'user_id' => $this->user->id
          ]);
          DB::table('users')
            ->where('id','=',$this->user->id)
            ->decrement('credit', $amount);

          dispatch(
            new SeasonPoint(
              'add points',
              $this->user->id,
              'Guess',
              'play_game',
              1)
          )->onQueue('low');

          $game->participants()->create([
            'round' => 0,
            'extra_info' => [
              'amount' => $amount
            ],
            'score' => $bet_on_key,
            'username' => $this->user->username,
          ]);
          $game->total_participants += 1;
          $game->save();
          $my_guesses = $game->participants()->where('username','=', $this->user->username)->get();
          $guesses = [];
          foreach ($my_guesses as $my_guess) {
            $key = getDiceKeyFromShortId($my_guess->score);
            if(!$key) {
              return;
            }
            if(array_key_exists(getDiceWordFromShortKey($key), $guesses)) {
              $guesses[getDiceWordFromShortKey($key)] = $guesses[getDiceWordFromShortKey($key)] + $my_guess->extra_info['amount'];
            } else {
              $guesses[getDiceWordFromShortKey($key)] = $my_guess->extra_info['amount'];
            }
          }
          $message = $this->user->username.' has placed '.number_format($amount,2,'.','').' credits on '.$bet_on_word.'\nGuesses: ';
          foreach ($guesses as $key => $guess) {
            $message .= $key.': '.number_format($guess,2,'.','').' credits ';
          }
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
          break;
        case 'finalize start':
          $game = Game::where('id','=', $this->model->game->id)->first();
          $message = 'Bot is calculating result. This may take a while depending on the number of guesses and players. Please wait...';
          $bot = 'Guess Bot';
          event(new SendGameMessage($this->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
          dispatch(new DiceGameJob("calculate result",$this->type, $this->model, $this->user))->delay(now()->addSeconds(2))->onQueue('high');
          break;
        case 'set bot':
          $id = $this->model->id;
          if($this->model->game()->exists()) {
            event(new InfoCommand("Bot cannot be set. Bot is already running.",$this->user->id,$this->type, $id));
          } else {
            $game = new Game();
            $game->owner_id = $id;
            $game->game = 'dice-1';
            $game->type = $this->type;
            $game->action_man = $this->user->username;
            $game->game_id = uniqid();
            $game->save();
          }
          break;
        case 'clear bot':
          $id = $this->model->id;
          if($this->model->game()->exists() && $this->model->game->phase == 0) {
            $game = Game::where('owner_id', $this->model->id)->where('type','=',$this->type)->first();
            if($game) {
              $game->action_man = $this->user->username;
              $game->save();
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
