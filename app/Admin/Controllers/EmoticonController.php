<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\Emoticon\Entities\Emoticon;
use Modules\Emoticon\Entities\EmotionCategory;
use Symfony\Component\Console\Input\Input;

class EmoticonController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Emoticon list';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Emoticon());

        $grid->column('id', __('Id'));
        $grid->category("Category")->display(function($category) {
          return $category['title'];
        });
        $grid->column('title', __('Title'));
        $grid->column('img', __('Icon'))->image('', 24,24);
        $grid->column('name', __('Code'));
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
        $show = new Show(Emoticon::findOrFail($id));

        $show->field('id', __('Id'));
//        $show->category('emotion_category_id', __('Emotion'));
        $show->category("Category", function($category) {
            $category->setResource('/system/admin/routes/global/emotion-categories');
            $category->title();
            $category->price();
        });
        $show->field('name', __('Code'));
        $show->field('title', __('Name'));
        $show->field('img', __('Image'))->image();
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
      $all_categories = EmotionCategory::orderBy('order')->get();
      $categories = [];
      foreach ($all_categories as $category) {
        $categories[$category->id] = $category->title;
      }
      $form = new Form(new Emoticon());
      if(request()->has('emotion_category_id')) {
        $category = request()->emotion_category_id;
        $form->select('emotion_category_id',__('Emoticon category'))->options($categories)->default($category);
      } else {
        $form->select('emotion_category_id',__('Emoticon category'))->options($categories);
      }

      $form->text('name', __('Code'));
      $form->text('title', __('Name'));
      $form->image('img', __('Icon'));

        return $form;
    }
}
