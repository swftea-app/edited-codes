<?php

namespace Modules\BetSystem\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BettingGroup extends Model
{
    protected $fillable = [];
    protected $appends = ['total_bet_amount','start_time_left'];
    public function teams() {
      return $this->hasMany(BettingGroupTeam::class, 'betting_group_id');
    }
    public function category() {
      return $this->belongsTo(BettingCategory::class,'betting_category_id');
    }
    public function winner() {
      return $this->belongsTo(BettingGroupTeam::class,'winner_id');
    }
    public function getFteamAttribute() {
      return $this->teams()->first();
    }
    public function getSteamAttribute() {
      return $this->teams()->orderBy('id','DESC')->first();
    }
    public function getMyBetsAttribute() {
      if(Auth::check()) {
        $user = \auth()->user();
        $teams = [];
        foreach ($this->teams()->get() as $team) {
          $teams[] = $team->id;
        }
        $bets = BettingUser::whereIn('user_id',[$user->id])->whereIn('group_team_id', $teams)->with('team')->get();
        return $bets;
      } else {
        return [];
      }
    }
    public function getStartTimeLeftAttribute() {
      $now = Carbon::now();
      $start_time = Carbon::parse($this->start_time);
      $end_time = Carbon::parse($this->end_time);
      if($now->isAfter($start_time) && $now->isBefore($end_time)) {
        return 0;
      }
      if($now->isBefore($start_time)) {
        return $start_time->diffInSeconds($now);
      }
      if($this->is_no_result) {
        return -2;
      }
      if($now->isAfter($end_time)) {
        return -1;
      }
    }
    public function getEndTimeLeftAttribute() {
      $now = Carbon::now();
      $start_time = Carbon::parse($this->start_time);
      $end_time = Carbon::parse($this->end_time);
      if($now->isAfter($start_time) && $now->isBefore($end_time)) {
        return $end_time->diffInSeconds($now);
      }
      return 0;
    }
    public function getTotalBetAmountAttribute() {
      $teams = $this->teams()->get();
      $amount = 0;
      foreach ($teams as $team) {
        $amount += $team->total_bet_amount;
      }
      return $amount;
    }
    public function getTotalBetsAttribute() {
      $teams = $this->teams()->get();
      $bets = 0;
      foreach ($teams as $team) {
        $bets += $team->bets_count;
      }
      return $bets;
    }
    public function getBetsParticipantsAttribute() {
      $teams = $this->teams()->get();
      $bets = [];
      foreach ($teams as $team) {
        foreach ($team->bets()->get() as $team_bet) {
          $username = $team_bet->user()->first()->username;
          if(!in_array($username, $bets)) {
            $bets[] = $username;
          }
        }
      }
      return $bets;
    }


}
