<?php

namespace App\Admin\Actions\Modules\Usersystem\Entities;

use Carbon\Carbon;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class ChampaignColor extends RowAction
{
    public $name = 'Award as Champ.';

    public function handle(Model $model) {
      $champ_till = Carbon::now()->addDays(15);
      $model->champ_till = $champ_till;
      $model->save();
      return $this->response()->success('Color given till '.$champ_till->format(config('constants.date_format')))->refresh();
    }
    public function dialog() {
      $this->confirm('Are you sure to give champ color to this user?');
    }
}