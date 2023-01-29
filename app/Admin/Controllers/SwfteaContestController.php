<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\Swfteacontest\Entities\SwfteaContest;

class SwfteaContestController extends AdminController
{
  /**
   * Title for current resource.
   *
   * @var string
   */
  protected $title = 'Swftea contest';

  /**
   * Make a grid builder.
   *
   * @return Grid
   */
  protected function grid()
  {
    $grid = new Grid(new SwfteaContest());

    $grid->column('id', __('Id'));
    $grid->column('title', __('Title'));
    $grid->column('description', __('Description'));
    $grid->column('start_at', __('Start at'))->date(config('constants.date_format'));
    $grid->column('end_at', __('End at'))->date(config('constants.date_format'));
    $grid->column('created_at', __('Created at'))->date(config('constants.date_format'));
    $grid->column('updated_at', __('Updated at'))->date(config('constants.date_format'));

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
    $show = new Show(SwfteaContest::findOrFail($id));

    $show->field('id', __('Id'));
    $show->field('title', __('Title'));
    $show->field('image', __('Image'))->image();
    $show->field('banners', __('Banners'))->json();
    $show->field('description', __('Description'));
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
    $form = new Form(new SwfteaContest());
    $form->column(1/2, function ($form) {
      $form->text('title', __('Title'))->required();
      $form->image('image', __('Image'))->required();
      $form->multipleImage('banners', __('Banners'));
      $form->textarea('description', __('Description'))->required();
      if(Admin::user()->isAdministrator()) {
        $states = [
          'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
          'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
        ];
        $form->switch('resolved','Resolved?')->states($states);
      }
      $form->datetime('start_at', __('Start at'))->default(date('Y-m-d H:i:s'))->required();
      $form->datetime('end_at', __('End at'))->default(date('Y-m-d H:i:s'))->required();
    });
    $form->column(1/2, function ($form) {
      $form->hasMany('terms', __('Terms and Conditions'), function (Form\NestedForm $form) {
        $form->textarea('text',__('Term'))->required();
      });
    });
    return $form;
  }
}
