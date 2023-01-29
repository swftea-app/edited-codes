<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Modules\SwfteaMission\Entities\MissionTask;
use \Modules\SwfteaMission\Entities\MissionWeek;

class MissionWeekController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Season Week';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MissionWeek());
        $grid->actions(function ($actions) {
          $actions->disableDelete();
          $actions->disableView();
        });
      $grid->disableCreateButton();
      $grid->column('id', __('Id'));
        $grid->column('season', __('Season'))->display(function () {
          return $this->season->name;
        });
        $grid->column('name', __('Name'))->display(function () {
          return 'S'.$this->season->id.': '.$this->name;
        })->expand(function ($model) {
          $box = new Box("Tasks");
          $tasks = $model->tasks()->get();
          $total_srp = 0;
          $total_rewards = 0;
          $all_tasks = [];
          foreach ($tasks as $task) {
            $total_srp += $task->srp;
            $total_rewards += $task->reward;
            $all_tasks[] = [
              $task->name,
              $task->type,
              $task->amount,
              $task->identifier,
              $task->abstract,
              $task->srp,
              $task->reward,
            ];
          }
          $all_tasks[] = [
            'Total',
            '',
            '',
            '',
            '',
            $total_srp,
            $total_rewards
          ];
          $all_tasks = new Table(["Name","Type","Amount","Identifier","Abstract","SRP","Reward"], $all_tasks);
          $box->content($all_tasks);
          return $box;
        });
        $grid->column('points', __('Points'));

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
        $show = new Show(MissionWeek::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('banner', __('Banner'))->image();
        $show->field('background', __('Background'))->image();
        $show->field('abstract', __('Abstract'));
        $show->field('points', __('Points'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new MissionWeek());
        $form->text('name', __('Name'))->readonly();
        $form->multipleSelect('tasks','Tasks')->options(MissionTask::all()->pluck('name','id'))->required();

        return $form;
    }
}
