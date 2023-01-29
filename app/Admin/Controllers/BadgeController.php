<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\Badge\Entities\Badge;
use \Modules\ChatMini\Entities\Announcement;
use Modules\Notifications\Jobs\NotificationJob;

class BadgeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Badges';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Badge());

        $grid->column('name', __('Name'));
        $grid->column('min_level', __('Min Level'));
        $grid->column('image', __('Image'))->image();
        $grid->column('created_at', __('Created at'))->date(config('constants.date_format'));

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
        $show = new Show(Badge::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Abstract'));
        $show->field('image', __('Image'))->image(null, 80,80);
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
        $form = new Form(new Badge());

        $form->text('name', __('Name'))->required();
        $form->image('image', __('Image'))->required();
        $form->textarea('description', __('Description'))->required();
        $form->number('min_level', __('Min Level'))->required();
        $form->saved(function (Form $form) {
          dispatch(new NotificationJob("admin_info_notification",(object)[
            'message' => 'New badge introduced. Review on admin panel.',
            'title' => 'Alert',
          ]))->onQueue('low');
        });
        return $form;
    }
}
