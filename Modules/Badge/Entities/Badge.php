<?php

namespace Modules\Badge\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserSystem\Entities\User;

class Badge extends Model {
  protected $fillable = [];
  public function users() {
    return $this->belongsToMany('\\Modules\\UserSystem\\Entities\\User', "user_badges","badge_id","user_id");
  }
}
