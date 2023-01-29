<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\ChatMini\Entities\Announcement;
use Modules\Notifications\Jobs\NotificationJob;

class AnnouncementController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Announcements';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Announcement());

        $grid->column('id', __('Id'));
        $grid->column('title', __('Title'));
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
        $show = new Show(Announcement::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('abstract', __('Abstract'));
        $show->field('image', __('Image'))->image();
        $show->description()->as(function ($content) {
          return "{$content}";
        });
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
        $form = new Form(new Announcement());

        $form->text('title', __('Title'))->required();
        $form->textarea('abstract', __('Abstract'))->required();
        $form->image('image', __('Image'));
        $form->summernote('description', __('Description'))->required();
        $form->saved(function (Form $form) {
          dispatch(new NotificationJob("new_announcement_added",(object)[]))->onQueue('low');
        });
        return $form;
    }
}
