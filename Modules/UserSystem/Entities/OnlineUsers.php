<?php

namespace Modules\UserSystem\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OnlineUsers extends Model
{
    protected $fillable = ['user_id','is_offline'];
    public function scopeOffline($query) {
      $last_updated = Carbon::now()->subSeconds(600);
      $query->where('is_offline', '=', 1)->where('updated_at', '<=', $last_updated);
    }
}
