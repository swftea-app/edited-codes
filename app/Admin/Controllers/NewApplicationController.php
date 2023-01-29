<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Modules\Program\Entities\ProgramApplication;
use App\Admin\Actions\Modules\Program\Entities\ProgramApplicationReject;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\ChatMini\Entities\Announcement;
use Modules\Program\Entities\NewPromotions;

class NewApplicationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'New Promotions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new NewPromotions());
        $grid->actions(function ($actions) {
          $actions->disableDelete();
          $actions->disableEdit();
          $actions->disableView();
          $actions->add(new ProgramApplication);
          $actions->add(new ProgramApplicationReject);
        });
        $grid->setActionClass(Grid\Displayers\DropdownActions::class);
        $grid->disableCreateButton();
        $grid->model()->orderBy("resolved");
        $grid->model()->orderBy("id","DESC");
        $grid->column('id', __('Request ID'))->sortable();
        $grid->column('type', __('Type'))->sortable();
        $grid->user(__('Sender'))->display(function ($user) {
          return $user['name'].' ('.$user['username'].')';
        });
        $grid->under(__('Under'))->display(function ($user) {
          return $user['name'].' ('.$user['username'].')';
        });
        $grid->column('resolved', __('Resolved?'))->display(function () {
          return $this->resolved ? "Yes" : "No";
        })->sortable();
        $grid->column('created_at', __('Created'))->date(config('constants.date_format'));
        $grid->column('updated_at', __('Updated'))->date(config('constants.date_format'));

        return $grid;
    }

}
