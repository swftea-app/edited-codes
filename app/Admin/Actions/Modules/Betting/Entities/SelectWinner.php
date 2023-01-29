<?php

namespace App\Admin\Actions\Modules\Bettings\Entities;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BetSystem\Entities\BettingGroup;
use Modules\BetSystem\Jobs\BettingJob;
use Modules\Games\Entities\Leaderboard;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\User;

class SelectWinner extends RowAction
{
  public $name = 'Select Winner';

  public function handle(Model $model, Request $request)
  {
    $winner = $request->get('winner');
    $winner_note = $request->get('winner_note');
    $is_no_result = $request->get('is_no_result');
    dispatch(new BettingJob('select_winner', (object)[
      'bet_id' => $model->id,
      'winner' => $winner,
      'note' => $winner_note,
      'is_no_result' => $is_no_result
    ]))->onQueue('low');
    return $this->response()->success('Winner selected.')->refresh();
  }

  public function form()
  {
    $teams = [];
    $group = BettingGroup::where('id', '=', $this->row->id)->with('teams.details')->first();
    foreach ($group->teams as $list) {
      $teams[$list->id] = $list->details->name;
    }
    $this->radio("is_no_result", "Is result published?")->options([0 => 'YES', 1 => 'NO'])->required();
    $this->radio("winner", "Winner")->options($teams)->required();
    $this->textarea("winner_note")->required();
  }
}