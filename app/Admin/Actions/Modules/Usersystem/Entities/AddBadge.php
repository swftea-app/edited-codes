<?php

namespace App\Admin\Actions\Modules\Usersystem\Entities;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Badge\Entities\Badge;

class AddBadge extends RowAction
{
    public $name = 'Reward Badge';

    public function handle(Model $model, Request $request) {
      $badges = $request->get('badges');
      $model->badges()->attach($badges);
      return $this->response()->success('Badge added.')->refresh();
    }
    public function form() {
      $this->multipleSelect('badges','Badges')->options(Badge::all()->pluck('name','id'));
    }
}