<?php

namespace Modules\BetSystem\Entities;

use Illuminate\Database\Eloquent\Model;

class BettingTeam extends Model
{
    protected $fillable = [];
    public function category() {
      return $this->belongsTo(BettingCategory::class,'betting_category_id');
    }
}
