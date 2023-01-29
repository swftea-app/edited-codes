<?php

namespace Modules\SwfteaMission\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\SwfteaMission\Entities\MissionScore;
use Modules\SwfteaMission\Entities\MissionSeason;
use Modules\SwfteaMission\Entities\MissionTask;
use Modules\SwfteaMission\Entities\MissionWeekPoint;
use function GuzzleHttp\Psr7\str;

class SeasonPoint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user_id;
    private $type;
    private $identifier;
    private $amount;
    private $stype;
    public function __construct($stype, $user_id, $type, $identifier, $amount){
        $this->user_id = $user_id;
        $this->type = strtolower($type);
        $this->identifier = strtolower($identifier);
        $this->amount = $amount;
        $this->stype = $stype;
    }
    public function handle() {
      $seasons = MissionSeason::where('resolved','=',false)->get();
      $seasons->append('is_active');
      $seasons->append('active_week');
      $active_season = null;
      foreach ($seasons as $season) {
        if($season->is_active) {
          $active_season = $season;
          break;
        }
      }
      if($active_season == null) return;
      $active_week = $active_season->active_week;
      if($active_week == null) return;
      $is_in = DB::table('mission_particiants')
        ->where('season_id','=',$active_season->id)
        ->where('user_id','=', $this->user_id)
        ->exists();
      if(!$is_in) return;
      # GET LIST OF TASKS

      switch ($this->stype) {
        case 'add points':
          $tasks = $active_week->tasks()->get();
          foreach ($tasks as $task) {
            if(strtolower($task->type) == $this->type && strtolower($task->identifier) == $this->identifier) {
              $points = new MissionScore();
              $points->user_id = $this->user_id;
              $points->week_id = $active_week->id;
              $points->task_id = $task->id;
              $points->amount = $this->amount;
              $points->save();
            }
          }
          break;
        default:
      }
    }
}
