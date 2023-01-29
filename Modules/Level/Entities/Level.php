<?php

namespace Modules\Level\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Jobs\NotificationJob;

class Level extends Model
{
    protected $fillable = ['user_id','name','value'];
    protected static function boot() {
      parent::boot();
      static::created(function($model) {
        if($model->value > 1) {
          dispatch(new NotificationJob('level_update', $model));
        }
      });
    }
    public function scopeRegistrations($query, $before = 0) {
      return $query->whereDate('created_at', '=', today()->subDays($before))->where('value','>', 1)->count();
    }
    public function user() {
      return $this->hasOne('\\Modules\\UserSystem\\Entities\\User');
    }
}
