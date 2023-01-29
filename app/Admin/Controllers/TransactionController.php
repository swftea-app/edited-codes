<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Modules\ChatMini\Entities\ApproveTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use \Modules\ChatMini\Entities\AdminTransaction;
use Modules\ChatMini\Entities\TransactionComments;

class TransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Transaction Histories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AdminTransaction());
        $grid->actions(function ($actions) {
          if(!Admin::user()->isAdministrator()) {
            $actions->disableDelete();
            $actions->disableEdit();
          }
          if(Admin::user()->can('approve.transactions')) {
            $actions->add(new ApproveTransaction());
          }
        });
        $grid->filter(function ($filter) {
          $filter->like('title',"Title");
        });
        $grid->model()->orderBy("updated_at","DESC");
        $grid->model()->where('resolved','=',false);
        $grid->column('id', __('Request Id'));
        $grid->column('flagged', __('Request Processed?'))->bool()->sortable();
        if(!Admin::user()->isAdministrator()) {
          $grid->model()->where("resolved",'=', false);
        } else {
          $grid->model()->orderBy("resolved");
          $grid->column('resolved', __('Resolved?'))->bool()->sortable();
        }
        $grid->column('title', __('Title'))->modal('Comments', function ($model) {
          $comments = $model->comments()->take(200)->get()->map(function ($comment) {
            return $comment->only(['commentor','comment','created_at']);
          });
          return new Table(['User','Content','Created at'], $comments->toArray());
        });
        $grid->column('special_comment', __('Comments'));
        $grid->column('updated_at', __('Updated at'))->date(config('constants.date_format'))->sortable();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(AdminTransaction::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('special_comment', __('Major comments'));
        $show->field('flagged', __('Flagged'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->panel()->tools(function ($tools) {
          $tools->disableEdit();
          $tools->disableDelete();
        });
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdminTransaction());
        $form->text('title', __('Title'))->required();
        $form->textarea('description', __('Amount Description'))->required();
        if(Admin::user()->isAdministrator()) {
          $form->textarea('special_comment', __('Special comment?'));
        }
        if(Admin::user()->isAdministrator()) {
          $states = [
            'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
          ];
          $form->switch('resolved','Resolved?')->states($states);
        }
        $form->saved(function (Form $form) {
          if(!empty($form->model()->special_comment)) {
            $form->model()->comments()->create([
              'comment' => 'Updated comment to '.$form->model()->special_comment,
              'commentor' => Admin::user()->name
            ]);
          }
        });
        return $form;
    }
}
