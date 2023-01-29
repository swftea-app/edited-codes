<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Modules\Usersystem\Entities\AddBadge;
use App\Admin\Actions\Modules\Usersystem\Entities\AddRole;
use App\Admin\Actions\Modules\Usersystem\Entities\ChampaignColor;
use App\Admin\Actions\Modules\Usersystem\Entities\ClearTag;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Modules\Badge\Entities\Badge;
use Modules\ChatMini\Jobs\SystemNotification;
use Modules\Notifications\Jobs\NotificationJob;
use \Modules\UserSystem\Entities\User;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'All users';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->actions(function ($actions) {
          $actions->disableDelete();
          $actions->disableView();
          $actions->add(new ClearTag);
          $actions->add(new AddBadge);
          $actions->add(new ChampaignColor);
          $actions->add(new AddRole);
        });

        $grid->model()->where('id','!=',1);
        $grid->model()->orderBy('id','DESC');
        $grid->disableCreateButton();
        $grid->filter(function ($filter) {
          $filter->disableIdFilter();
          $filter->like("username", "Username");
          $filter->like("email", "Email");
          $filter->equal('pres')->radio([
            ''   => 'All',
            'online'    => 'Online',
            'offline'    => 'Offline',
          ]);
          $filter->where(function ($query) {
            $query->whereHas('profile', function ($query) {
              $query->where('userIp', 'like', "%{$this->input}%");
            });
          }, 'IP');
        });
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->expand(function ($model) {
          $tab = new Tab();
          # Profile
          $profile = new Table(['IP','Country','Token','Level Bar','Spent (lv)','Spent (t)','Transferred (t)'], [[
            $model->profile->userIp,
            $model->profile->countryCode,
            $model->profile->verification_token,
            $model->profile->level_bar,
            $model->profile->spent_for_next_level,
            $model->profile->today_spent_amount,
            $model->profile->today_transferred_amount,
          ]]);
          # levels
          $all_levels = $model->levels()->orderBy('id','DESC')->get()->map(function($level) {
            return $level->only(['name','value','created_at']);
          });
          $level_block = new Table(['Level name','Value','Level updated on'], $all_levels->toArray());

          # account histories
          $all_histories = $model->histories()->orderBy('id','DESC')->take(50)->get()->map(function($history) {
            return $history->only(['type','creditor','old_value','new_value','message','created_at']);
          });
          $histories_block = new Table(["Type","Creditor","Old Value","New Value","Message","Created on"], $all_histories->toArray());

          # Tags
          $all_tags = $model->tags()->get()->map(function($history) {
            return $history->only(['username']);
          });
          $tags_block = new Table(["Username"], $all_tags->toArray());

          $tab->add('User Profile', $profile);
          $tab->add('Levels', $level_block);
          $tab->add('Account Histories', $histories_block);
          $tab->add('User tags', $tags_block);
          return new Box('Profile Details', $tab);
        });
        $grid->column('profile_picture', __('Profile Picture'))->image(null,80,80);
        $grid->column('email', __('Email'));
        $grid->column('taggedBy.username',__('Tagged by'));
        $grid->column('username', __('Username'))->modal("Logins", function ($model) {
          $logins = $model->logins()->take(500)->latest()->get()->map(function ($login) {
            return $login->only(['ip','countryName','cityName','countryCode','latitude','longitude','device_type','device_name','device_id','action']);
          });
          return new Table(['IP','Country','City','countryCode','latitude','longitude','device_type','device_name','device_id','action'], $logins->toArray());
        });
        $grid->column('credit', __('Credit'))->sortable();
        $grid->column('badge_count', __('Badges'))->display(function () {
          return $this->badges()->count();
        })->modal("Badges", function ($model) {
          $badges = $model->badges()->get()->map(function ($badge) {
            return $badge->only(['name']);
          });
          return new Table(['Name'], $badges->toArray());
        });
        $grid->column('points', __('Points'))->sortable();
        $grid->column('pres', __('Presence'))->sortable();
        $grid->column('created_at', __('Member since'))->display(function () {
          return date("F j, Y, g:i a",strtotime($this->created_at));
        });
      $grid->column('champ_till', __('Champ till'))->display(function () {
        return is_null($this->champ_till) ? "NONE" : date("F j, Y, g:i a",strtotime($this->champ_till));
      });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id) {
      $show = new Show(User::findOrFail($id));
      $show->getModel()->setVisible(['email','credit']);
      $show->field('id', __('Id'));
      $show->field('name', __('Name'));
      $show->field('email', __('Email'));
      $show->field('username', __('Username'));
      $show->field('main_status', __('Status message'));
      $show->field('email_verified_at', __('Email verified at'));
      $show->field('credit', __('Credit'));
      $show->field('points', __('Points'));
      $show->field('status', __('Status'));
      $show->field('created_at', __('Created at'));
      $show->field('pres', __('Pres'));
      $show->panel()->tools(function ($tools) {
        $tools->disableDelete();
        $tools->disableCreate();
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
        $form = new Form(new User());

        $form->model()->makeVisible(['email','credit','pin']);
        $form->text('name', __('Name'))->default('Max Mastermind');
        $form->number('points', __('Points'));
        $form->text('pin', __('Pin'));
        $form->number('tag_id', __('Tag ID'));
        $form->email('email',"Email")->required();
        $states = [
          'on'  => ['value' => 1, 'text' => 'No', 'color' => 'danger'],
          'off' => ['value' => 0, 'text' => 'Yes', 'color' => 'success'],
        ];
        $form->switch('status','Blocked?')->states($states);
        $form->select('gender','Gender')->options(['Male' => 'Male','Female'=>'Female'])->required();
        $form->multipleSelect('badges','Badges')->options(Badge::all()->pluck('name','id'));
        $form->text('country','Country')->required();
        $form->datetime('champ_till',__('Show champ color till?'));
        $form->textarea('main_status', __('Main status'));

        $form->saved(function (Form $form) {
          if($form->model()->status == 0) {
            dispatch(new NotificationJob('account_suspended', (object) [
              'user_id' => $form->model()->id
            ]));
          }
        });

        return $form;
    }
}
