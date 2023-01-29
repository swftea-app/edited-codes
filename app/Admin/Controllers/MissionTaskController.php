<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\SwfteaMission\Entities\MissionTask;

class MissionTaskController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Task controller';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MissionTask());
        $grid->model()->orderBy('id','DESC');

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('type', __('Type'));
        $grid->column('amount', __('Amount'));
        $grid->column('identifier', __('Identifier'));
        $grid->column('abstract', __('Abstract'));
        $grid->column('banner', __('Banner'))->image();
        $grid->column('reward', __('Reward (CR)'));
        $grid->column('srp', __('SRP'));

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
        $show = new Show(MissionTask::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('type', __('Type'));
        $show->field('amount', __('Amount'));
        $show->field('identifier', __('Identifier'));
        $show->field('abstract', __('Abstract'));
        $show->field('banner', __('Banner'))->image();
        $show->field('reward', __('Reward'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new MissionTask());

        $form->text('name', __('Name'))->required();
        $form->select('identifier',__('Identifier'))->options(config('constants.mission.action.identifier',[]))->required();
        $form->select('type',__('Type'))->options(config('constants.mission.action.types',[]))->required();
        $form->number('amount', __('Amount'))->default(1)->required();
        $form->textarea('abstract', __('Abstract'))->required();
        $form->image('banner', __('Banner'))->required();
        $form->decimal('reward', __('Reward'))->required();
        $form->number('srp', __('SRP'))->required()->default(10);

        return $form;
    }
}
