<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\BetSystem\Entities\BettingCategory;

class BettingCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Betting Category';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BettingCategory());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('abstract', __('Abstract'));
        $grid->column('banner', __('Banner'))->image();
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(BettingCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('abstract', __('Abstract'));
        $show->field('banner', __('Banner'))->image();
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->teams("Teams", function ($emoticons) {
          $emoticons->resource("/system/admin/routes/global/betting-teams");
          $emoticons->name();
          $emoticons->column('photo',__('Image'))->image();
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
        $form = new Form(new BettingCategory());

        $form->text('name', __('Name'))->required();
        $form->textarea('abstract', __('Abstract'))->required();
        $form->image('banner', __('Banner'))->required();

        return $form;
    }
}
