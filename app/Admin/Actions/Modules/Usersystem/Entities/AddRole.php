<?php

namespace App\Admin\Actions\Modules\Usersystem\Entities;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\Badge\Entities\Badge;

class AddRole extends RowAction
{
    public $name = 'Assign Role';

    public function handle(Model $model, Request $request) {
      $roles = $request->get('roles');
      $model->syncRoles(explode(",", $roles));
      return $this->response()->success($roles.' is provided.')->refresh();
    }
    public function form() {
      $this->radio('roles','Role')->options([
        'User' => 'User',
        'Legends' => 'Legends',
        'Global Admin' => 'Global Admin',
        'Mentor Head' => 'Mentor Head',
      ]);
    }
}