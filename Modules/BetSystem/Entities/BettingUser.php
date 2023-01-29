<?php

namespace Modules\BetSystem\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserSystem\Entities\User;

class BettingUser extends Model
{
    protected $fillable = ['user_id','amount'];
    public function user() {
      return $this->belongsTo(User::class,'user_id');
    }
    public function team() {
      return $this->belongsTo(BettingGroupTeam::class,'group_team_id');
    }
}
