<?php

namespace Modules\Swfteacontest\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SwfteaContest extends Model
{
  protected $fillable = [];
  protected $appends = [
    'start_time',
    'end_time',
    'phase',
  ];
  protected $casts = [
    'banners' => 'array'
  ];

  public function terms()
  {
    return $this->hasMany(SwfteaContestTermsAndContition::class, 'swfteacontest_id');
  }

  public function getStartTimeAttribute() {
    $start_at = Carbon::parse($this->start_at);
    $now = Carbon::now();
    return $start_at->diffInSeconds($now);
  }
  public function getEndTimeAttribute() {
    $end_at = Carbon::parse($this->end_at);
    $now = Carbon::now();
    return $end_at->diffInSeconds($now);
  }
  public function getPhaseAttribute() {
    $end_at = Carbon::parse($this->end_at);
    $start_at = Carbon::parse($this->start_at);
    $now = Carbon::now();
    if($now->isBefore($start_at)) {
      return 'NOT_STARTED';
    } else if($now->isAfter($start_at) && $now->isBefore($end_at)) {
      return 'RUNNING';
    } else {
      return 'ENDED';
    }
  }
}
