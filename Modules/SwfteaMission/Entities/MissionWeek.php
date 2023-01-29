<?php

namespace Modules\SwfteaMission\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MissionWeek extends Model
{
    protected $fillable = [
      'season_id',
      'name',
      'banner',
      'background',
      'abstract',
      'points',
    ];
    public function season() {
      return $this->belongsTo(MissionSeason::class,"season_id");
    }
    public function tasks() {
      return $this->belongsToMany(MissionTask::class,'week_tasks','week_id','task_id');
    }

    public function getIsActiveAttribute() {
      $season = $this->season()->first();
      if($season->is_active) {
        $active_week = $season->active_week;
        return $active_week->id == $this->id;
      } else {
        return false;
      }
    }

    public function getExpireTimeAttribute() {
      $season = $this->season()->first();
      if($season->is_active) {
        $start = Carbon::parse($season->start_at);
        $end = Carbon::parse($season->end_at);
        $now = Carbon::now();
        $current = $now->diffInSeconds($start);
        $total = $end->diffInSeconds($start);
//        $week = $total / 4;
        $week = 7 * 24 * 60 * 60;
        if($current < $week) {
          return intval($week - $current);
        } elseif ($current < ($week * 2)) {
          return intval(($week * 2) - $current);
        } elseif ($current < ($week * 3)) {
          return intval(($week * 3) - $current);
        } elseif ($current < ($week * 4)) {
          return intval(($week * 4) - $current);
        } else {
          return 0;
        }
      } else {
        return 0;
      }
    }

}
