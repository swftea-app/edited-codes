<?php

namespace App\Admin\Controllers;

use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use \Modules\Emoticon\Entities\EmotionCategory;

class EmoticonsCategoryController extends AdminController {
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Emoticon Category';



  protected function grid()
    {
        $grid = new Grid(new EmotionCategory());

        $grid->column('id', __('Id'));
        $grid->column('title', __('Title'));
        $grid->column('price', __('Price'));
        $grid->column('discount', __('Discount'));
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
        $show = new Show(EmotionCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('image',__('Category Image'))->image();
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('price', __('Price'));
        $show->field('discount', __('Discount'));
        $show->field('icon_type', __('Icon'));
        $show->field('order', __('Order'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->emoticons("Emoticons", function ($emoticons) {
          $emoticons->resource("/system/admin/routes/global/emoticons");
          $emoticons->title();
          $emoticons->name();
          $emoticons->column('img',__('Image'))->image();
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
        $form = new Form(new EmotionCategory());

        $form->text('title', __('Title'))->required();
        $form->textarea('description', __('Description'))->required();
        $form->image('image',"Category image")->required();
        $form->decimal('price', __('Price'))->required();
        $form->decimal('discount', __('Discount'))->default(0.00)->required();
        $form->icon('icon_type', __('Icon '))->default('fa-smile-o')->required();

        return $form;
    }
}
