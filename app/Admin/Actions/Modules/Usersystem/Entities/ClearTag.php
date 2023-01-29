<?php

namespace App\Admin\Actions\Modules\Usersystem\Entities;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class ClearTag extends RowAction
{
    public $name = 'Clear Tag';

    public function handle(Model $model) {
      $model->tag_id = 1;
      $model->save();
      return $this->response()->success('Tag cleared.')->refresh();
    }
    public function dialog() {
      $this->confirm('Are you sure to clear tag of this user?');
    }
}