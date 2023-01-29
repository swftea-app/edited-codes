<?php

namespace Modules\Program\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\UserSystem\Entities\User;

class ProgramApplication extends Model {
    protected $fillable = [];
    protected $appends = ['created_on'];
    protected static function boot() {
      parent::boot();
      static::created(function ($application) {
        if($application->type == 'merchantship') {
          dispatch(new NotificationJob('merchantship', $application));
        }
        if($application->type == 'mentorship') {
          dispatch(new NotificationJob('mentorship', $application));
        }
      });
    }
    public function getCreatedOnAttribute() {
      return Carbon::parse($this->created_at)->diffForHumans();
    }
    public function head_person() {
      return $this->belongsTo(User::class,'under_of','id');
    }
    public function sender() {
      return $this->belongsTo(User::class,'user_id','id');
    }
}
