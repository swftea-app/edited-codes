<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\Avatar\Entities\AvatarItem;
use Modules\Avatar\Entities\AvatarKey;

class AvatarItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Avatar Item';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AvatarItem());

        $grid->column('id', __('Id'));
        $grid->column('item', __('Item'));
        $grid->column('name', __('Name'));
        $grid->column('price', __('Price'));

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
        $show = new Show(AvatarItem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('item', __('Item'));
        $show->field('avatar_key_id', __('Avatar key id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('price', __('Price'));
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
        $form = new Form(new AvatarItem());

        $form->text('item', __('Item'))->required();
        $form->select('avatar_key_id', __('Avatar key'))->options(AvatarKey::all()->pluck('name','id'))->required();
        $form->text('name', __('Name'))->required();
        $form->textarea('description', __('Description'))->required();
        $form->decimal('price', __('Price'))->default(0.00);

        return $form;
    }
}
