<?php

namespace Modules\SwfteaMission\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserSystem\Entities\User;

class SeasonMilestone extends Model
{
    protected $fillable = [
      'name',
      'description',
      'target',
      'reward',
    ];
    public function users() {
      return $this->belongsToMany(User::class,'milestone_users','milestone_id','user_id');
    }
}
