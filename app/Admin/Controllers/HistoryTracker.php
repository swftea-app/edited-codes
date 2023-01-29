<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use \Modules\AccountHistory\Entities\AccountHistory;

class HistoryTracker extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Account History Tracker';
    protected function grid()
    {
        $grid = new Grid(new AccountHistory());
        $grid->model()->orderBy("id","DESC");
        $grid->column('id', __('Id'));
        $grid->column('creditor', __('Creditor'));
        $grid->column('old_value', __('Old value'));
        $grid->column('new_value', __('New value'));
        $grid->column('message', __('Message'));
        $grid->column('amount','Amount')->display(function () {
          $amount = $this->new_value - $this->old_value;
          if($amount > 0) {
            return '+'.round($amount).' credits';
          } else {
            return round($amount).' credits';
          }
        })->label();
        $grid->column('user.username', __('User'));
        $grid->column('created_at', __('Created at'))->date(config('constants.date_format'));
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->filter(function ($filter) {
          $filter->disableIdFilter();
          $filter->like("message", "History Message LIKE");
          $filter->where(function ($query) {
            $query->whereHas('user', function ($query) {
              $query->where('username', 'like', "%{$this->input}%")->orWhere('email', 'like', "%{$this->input}%");
            });
          }, 'Username');
        });
        return $grid;
    }

}
