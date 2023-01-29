<?php

namespace Modules\BetSystem\Entities;

use Illuminate\Database\Eloquent\Model;

class BettingCategory extends Model
{
    protected $fillable = [];
    public function groups() {
      return $this->hasMany(BettingGroup::class,'betting_category_id');
    }
    public function teams() {
      return $this->hasMany(BettingTeam::class, 'betting_category_id');
    }
}
