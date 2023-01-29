<?php

namespace Modules\SwfteaMission\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MissionSeason extends Model
{
    protected $fillable = [];
    protected $appends = ['is_active','is_already_in'];
    public function getIsAlreadyInAttribute() {
      return DB::table('mission_particiants')
        ->where('user_id','=',auth()->id())
        ->where('season_id','=',$this->id)->exists();
    }
    public function getMeAttribute() {
      $user_id = auth()->id();
      $me = new \stdClass();
      $me->points = 0;
      $weeks = $this->weeks()->get();
      foreach ($weeks as $week) {
        $me->points += DB::table('mission_week_points')
          ->where('week_id','=',$week->id)
          ->where('user_id','=',$user_id)
          ->sum('points');
      }
      return $me;
    }
    public function getWeekPointAttribute() {
      $user_id = auth()->id();
      $active_week = $this->getActiveWeekAttribute();
      if($active_week != null) {
        $points = DB::table('mission_week_points')
          ->where('user_id','=',$user_id)
          ->where('week_id','=',$active_week->id)
          ->sum('points');
        return $points;
      }
      return 0;
    }
    public function participants() {
      return $this->hasMany(MissionParticiants::class,'season_id');
    }
    public function weeks() {
      return $this->hasMany(MissionWeek::class,"season_id");
    }
    public function milestones() {
      return $this->hasMany(SeasonMilestone::class,"season_id");
    }
    public function getIsActiveAttribute() {
      $start_time = Carbon::parse($this->start_at);
      $end_time = Carbon::parse($this->end_at);
      $now = Carbon::now();
      return ($now->isAfter($start_time) && $now->isBefore($end_time));
    }

    public function getActiveWeekAttribute() {
      if($this->getIsActiveAttribute()) {
        $start = Carbon::parse($this->start_at);
        $end = Carbon::parse($this->end_at);
        $now = Carbon::now();
        $current = $now->diffInSeconds($start);
        $total = $end->diffInSeconds($start);
//        $week = $total / 4;
        $week = 7 * 24 * 60 * 60;
        if($current < $week) {
          $week = $this->weeks()->first();
          $week->append('expire_time');
          return $week;
        } elseif ($current < ($week * 2)) {
          $week = $this->weeks()->skip(1)->take(1)->first();
          $week->append('expire_time');
          return $week;
        } elseif ($current < ($week * 3)) {
          $week = $this->weeks()->skip(2)->take(1)->first();
          $week->append('expire_time');
          return $week;
        } elseif ($current < ($week * 4)) {
          $week = $this->weeks()->skip(3)->take(1)->first();
          $week->append('expire_time');
          return $week;
        } else {
          return null;
        }
      } else {
        return null;
      }
    }
}
