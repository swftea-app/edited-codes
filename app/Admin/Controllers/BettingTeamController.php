<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\BetSystem\Entities\BettingCategory;
use \Modules\BetSystem\Entities\BettingTeam;

class BettingTeamController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Teams';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BettingTeam());

        $grid->column('id', __('Id'));
        $grid->category("Category")->display(function($category) {
          return $category['name'];
        });
        $grid->column('name', __('Name'));
        $grid->column('photo', __('Photo'))->image();

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
        $show = new Show(BettingTeam::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('betting_category_id', __('Betting category id'));
        $show->field('name', __('Name'));
        $show->field('abstract', __('Abstract'));
        $show->field('photo', __('Photo'));
        $show->field('banner', __('Banner'));
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
        $form = new Form(new BettingTeam());

        $form->select('betting_category_id', __('Category'))->options(BettingCategory::all()->pluck('name','id'))->required();
        $form->text('name', __('Name'))->required();
        $form->textarea('abstract', __('Abstract'))->required();
        $form->image('photo', __('Photo'))->required();
        $form->image('banner', __('Banner'))->required();

        return $form;
    }
}
