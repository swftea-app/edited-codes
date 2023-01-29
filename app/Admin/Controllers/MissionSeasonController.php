<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\Storage;
use \Modules\SwfteaMission\Entities\MissionSeason;
use Modules\SwfteaMission\Entities\MissionTask;

class MissionSeasonController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Seasons';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MissionSeason());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->expand(function ($model) {
          $tabs = new Tab();
          $weeks = $model->weeks()->get();
          foreach ($weeks as $week) {
            $details = new Table(["",""], [
              ["Banner", "<img src='".Storage::url($week->banner)."' width='150' height='100'/>"],
              ["Background", "<img src='".Storage::url($week->background)."' width='150' height='100'/>"],
              ["Abstract", $week->abstract],
              ["Points", $week->points],
            ]);
            $tasks = $week->tasks()->get()->map(function($task) {
              return $task->only(['name','type','amount','identifier','abstract', 'banner','reward']);
            });
            $all_tasks = new Table(["Name","Type","Amount","Identifier","Abstract","Banner","Reward"], $tasks->toArray());
            $week_tab = new Tab();
            $week_tab->add("Details", $details);
            $week_tab->add("Tasks", $all_tasks);
            $tabs->add($week->name, $week_tab);
          }
          $milestones = $model->milestones()->get();
          $week_tab = new Tab();
          foreach ($milestones as $milestone) {
            $all_participants = $milestone->users()->get();
            $users = [];
            foreach ($all_participants as $participant) {
              $users[] = [$participant->username];
            }
            $week_tab->add($milestone->name, new Table(["Username"], $users));
          }
          $tabs->add("Milestones", $week_tab);
          return new Box("Descriptions", $tabs);
        });
        $grid->column('points', __('Points'));
        $grid->column('start_at', __('Start at'));
        $grid->column('end_at', __('End at'));

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
        $show = new Show(MissionSeason::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('points', __('Points'));
        $show->field('banner', __('Banner'));
        $show->field('background', __('Background'));
        $show->field('start_at', __('Start at'));
        $show->field('end_at', __('End at'));
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
        $form = new Form(new MissionSeason());

        $form->column(1/2, function ($form) {
          $form->text('name', __('Name'))->required();
          $form->decimal('points', __('Points'))->required();
          $form->image('banner', __('Banner'))->required();
          $form->image('background', __('Background'))->required();
          $states = [
            'on'  => ['value' => 1, 'text' => 'Visible', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'Resolved', 'color' => 'danger'],
          ];
          $form->switch('resolved','Resolved')->states($states);
          $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'))->required();
          $form->datetime('end_at', __('End at'))->default(date('Y-m-d H:i:s'))->required();

          $form->hasMany('milestones', __('Milestones'), function (Form\NestedForm $form) {
            $form->text('name',__('Milestone name'))->required();
            $form->textarea('description',__('Abstract'))->required();
            $form->decimal('target',__('Target point'))->default(100)->required();
            $form->decimal('reward',__('Reward credit'))->default(1000)->required();
          });
        });
      $form->column(1/2, function ($form) {
        $form->hasMany('weeks', __('Weeks'), function (Form\NestedForm $form) {
          $form->text('name',__('Week name'))->required();
          $form->image('banner',__('Week banner'))->required();
          $form->image('background',__('Week background'))->required();
          $form->textarea('abstract',__('Abstract'))->required();
          $form->decimal('points',__('Target point'))->default(100)->required();
        });
      });



        return $form;
    }
}
