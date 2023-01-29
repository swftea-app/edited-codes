<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\Gift\Entities\AllGifts;

class GiftController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'All gifts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AllGifts());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('price', __('Price'));
        $grid->column('discounted_price',__('Discounted price'))->display(function () {
          return number_format($this->price - ($this->price * ($this->discount / 100)), 2);
        });
        $grid->column('visible',__('Visible in store?'))->display(function () {
          return $this->visible == 1 ? "Yes" : "No";
        });
        $grid->column('isPremium',__('Premium Gift?'))->display(function () {
          return $this->isPremium == 1 ? "Yes" : "No";
        });
        $grid->filter(function ($filter) {
          $filter->like("name", "Name");
          $filter->like("price", "Price");
        });
        $grid->column('gift_image', __('Gift image'))->image('',24,24);

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
        $show = new Show(AllGifts::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('price', __('Price'));
        $show->field('discount', __('Discount'));
        $show->field('gift_image', __('Gift image'))->image();
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
        $form = new Form(new AllGifts());

        $form->text('name', __('Name'))->required();
        $form->decimal('price', __('Price'))->required();
        $form->decimal('discount', __('Discount'))->help("In %")->default(0.00);
        $form->image('gift_image', __('Gift image'));
        $states = [
          'on'  => ['value' => 1, 'text' => 'Visible', 'color' => 'success'],
          'off' => ['value' => 0, 'text' => 'Hidden', 'color' => 'danger'],
        ];
        $form->switch('visible','Visible in gift list?')->states($states);
        $states = [
          'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
          'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
        ];
        $form->switch('isPremium','Premium gift?')->states($states);
        $form->color('color',__("Gift color"))->default('#E6397F');

        return $form;
    }
}
