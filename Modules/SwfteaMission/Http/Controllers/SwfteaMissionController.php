<?php

namespace Modules\SwfteaMission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SwfteaMission\Entities\MissionParticiants;
use Modules\SwfteaMission\Entities\MissionSeason;
use Modules\SwfteaMission\Entities\MissionTask;
use Modules\SwfteaMission\Entities\MissionWeekPoint;
use Modules\SwfteaMission\Entities\SeasonMilestone;
use Modules\UserSystem\Entities\User;

class SwfteaMissionController extends Controller {
  public function all() {
    $seasons = MissionSeason::where('resolved','=',false)->get();
    $seasons->append('is_active');
    $seasons->append('active_week');
    $seasons->append('is_already_in');
    return $seasons;
  }
  public function season($id) {
    $season = MissionSeason::where('id','=',$id)->with(['weeks','milestones.users'])->withCount(['participants'])->first();
    $season->append('active_week');
    $season->append('me');
    $season->append('is_active');
    $season->append('is_already_in');
    $season->append('week_point');

    foreach ($season->weeks as $key => $week) {
      if($season->active_week == null) {
        $week->load('tasks');
      } else {
        if($week->id <= $season->active_week->id) {
          $week->load('tasks');
        } else {
          $season->weeks[$key]->tasks = [];
        }
      }

      foreach ($week->tasks as $pey => $task) {
       # is completed
        $is_completed = DB::table('mission_week_points')
          ->where('week_id','=',$week->id)
          ->where('task_id','=',$task->id)
          ->where('user_id','=',auth()->id())
          ->exists();
        $season->weeks[$key]->tasks[$pey]->is_completed = $is_completed;

        $progress = DB::table('mission_scores')
          ->where('task_id','=',$task->id)
          ->where('user_id','=',auth()->id())
          ->where('week_id','=',$week->id)
          ->sum('amount');

        $season->weeks[$key]->tasks[$pey]->progress = $progress;
      }
    }
    // Season progress
    $season_milestones = $season->milestones()->get();
    $milestones = [
      ['type' => 'start','label' => 'START'],
    ];
    $my_points = $season->me->points;
    foreach ($season_milestones as $season_milestone) {
      if($season_milestone->target > $my_points) { // NOT COMPLETED
        if($season_milestone->target == 100) {
          $milestones[] = [
            'type' => '100',
            'label' => '100',
            'abstract' => $season_milestone->description,
            'reward' => $season_milestone->reward
          ];
        } else {
          $milestones[] = [
            'type' => 'notcompleted',
            'label' => $season_milestone->name,
            'abstract' => $season_milestone->description,
            'reward' => $season_milestone->reward
          ];
        }
      } else {
        if($season_milestone->users->contains(auth()->id())) {
          $milestones[] = [
            'type' => 'completed',
            'label' => $season_milestone->name,
            'abstract' => $season_milestone->description,
            'reward' => $season_milestone->reward
          ];
        } else {
          $milestones[] = [
            'type' => 'readytoopen',
            'label' => "OPEN",
            'id' => $season_milestone->id,
            'abstract' => $season_milestone->description,
            'reward' => $season_milestone->reward
          ];
        }
      }
    }
    $season->milestones_details = $milestones;
    $is_in = $season->is_already_in;
    if($season) {
      if($is_in) {
        return [
          'is_in' => true,
          'week_over' => $season->active_week == null,
          'season' => $season
        ];
      } else {
        return [
          'is_in' => false,
          'week_over' => $season->active_week == null,
        ];
      }
    }
  }
  public function join($id) {
    $season = MissionSeason::where('id','=',$id)->with(['weeks'])->withCount(['participants'])->first();
    $season->append('is_active');
    $season->append('is_already_in');
    $is_in = $season->is_already_in;

    if(!$is_in && $season->is_active) {
      $participant = new MissionParticiants();
      $participant->user_id = auth()->id();
      $participant->season_id = $season->id;
      $participant->save();
      return [
        'error' => false,
        'message' => 'You are in season.',
      ];
    } else {
      return [
        'error' => false,
        'message' => 'Some error occurred! Please contact administrator.',
      ];
    }
  }

  public function grabmainpoints($milestone_id) {
    $milestone = SeasonMilestone::where('id','=',$milestone_id)->first();
    if($milestone) {
      $season = MissionSeason::where('id','=',$milestone->season_id)->first();
      $season->append('me');
      if($season->me->points >= $milestone->target && !$milestone->users->contains(auth()->id())) {

        $user = User::where('id','=',auth()->id())->first();
        $user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'system',
          'creditor_id' => 1,
          'message' => 'Reward '.$milestone->reward.' credits from season for reaching milestone of '.$milestone->target.' SRP.',
          'old_value' => $user->credit,
          'new_value' => $user->credit + $milestone->reward,
          'user_id' => $user->id
        ]);
        DB::table('users')
          ->where('id','=',$user->id)
          ->increment('credit', $milestone->reward);

        $milestone->users()->attach(auth()->id());
        if($milestone->target != 0) {
          return [
            'error' => false,
            'message' => 'You have earned: '.$milestone->reward.' credits as bonus.'
          ];
        } else {
          return [
            'error' => false,
            'message' => 'You have earned: '.$milestone->reward.' credits as bonus. You will be notified shortly with your additional bonuses.'
          ];
        }
      } else {
        return [
          'error' => true,
          'message' => "You have already grabbed your prizes. Please reload the screen."
        ];
      }
    }
    return [
      'error' => true,
      'message' => "Some error occured. ".$milestone_id
    ];
  }

  public function collect($week_id, $task_id) {
    $is_completed = DB::table('mission_week_points')
      ->where('week_id','=',$week_id)
      ->where('task_id','=',$task_id)
      ->where('user_id','=',auth()->id())
      ->exists();
    $progress = DB::table('mission_scores')
      ->where('task_id','=',$task_id)
      ->where('user_id','=',auth()->id())
      ->where('week_id','=',$week_id)
      ->sum('amount');
    $task = MissionTask::where('id','=',$task_id)->first();
    if($task) {
      if(!$is_completed && $progress >= $task->amount) {
        $complete_week = new MissionWeekPoint();
        $complete_week->week_id = $week_id;
        $complete_week->user_id = auth()->id();
        $complete_week->task_id = $task_id;
        $complete_week->points = $task->srp;

        $complete_week->save();

        $user = User::find(auth()->id());
        $user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'admin',
          'creditor_id' => 1,
          'message' => "Reward ".$task->reward." credits for completing the task.",
          'old_value' => $user->credit,
          'new_value' => $user->credit + $task->reward,
          'user_id' => $user->id
        ]);
        DB::table('users')
          ->where('id','=',$user->id)
          ->increment('credit', $task->reward);
        return [
          'error' => false,
          'message' => 'You have successfully claimed the reward and SRP.',
          'srp' => $task->srp
        ];
      } else {
        return ['error' => true, 'message' => 'task not completed'];
      }
    }
    return ['error' => true, 'message' => 'No task defined. '.$task_id];
  }
}
