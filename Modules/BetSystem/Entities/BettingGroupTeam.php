<?php

namespace Modules\BetSystem\Entities;

use Illuminate\Database\Eloquent\Model;

class BettingGroupTeam extends Model {
    protected $fillable = ['team_id'];
    protected $appends = ['total_bet_amount','winning_rate'];
    protected $with = ['details'];
    protected $withCount = ['bets'];
    public function details() {
      return $this->belongsTo(BettingTeam::class,'team_id');
    }
    public function group() {
      return $this->belongsTo(BettingGroup::class,'betting_group_id');
    }
    public function bets() {
      return $this->hasMany(BettingUser::class,'group_team_id');
    }
    public function getTotalBetAmountAttribute() {
      return $this->bets()->sum('amount');
    }
    public function getWinningRateAttribute() {
      $total = $this->group()->first()->total_bet_amount;
      $group_amount = $this->total_bet_amount;
      try {
        $rate = round($total/$group_amount, 2);
//        if($rate > 2) {
//          $rate =  $rate - 0.5;
//        } else if($rate > 2) {
//          $rate =  $rate - 0.4;
//        } elseif($rate > 1.7) {
//          $rate =  $rate - 0.3;
//        } elseif($rate > 1.3) {
//          $rate =  $rate - 0.07;
//        } elseif($rate > 1.2) {
//          $rate =  $rate - 0.01;
//        }
        return round($rate);
      } catch (\Exception $e) {
        return 0;
      }
    }
}
