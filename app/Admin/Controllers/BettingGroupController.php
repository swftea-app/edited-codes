<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Modules\Bettings\Entities\SelectWinner;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Table;
use Modules\BetSystem\Entities\BettingCategory;
use \Modules\BetSystem\Entities\BettingGroup;
use Modules\BetSystem\Entities\BettingTeam;

class BettingGroupController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Betting Match';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BettingGroup());
        $grid->model()->where('resolved','=', false);
        $grid->column('id', __('Id'));
        $grid->category("Category")->display(function($category) {
          return $category['name'];
        });
        $grid->actions(function ($actions) {
          $actions->add(new SelectWinner);
        });
        $grid->column('title', __('Title'));
// >expand(function ($model) {
//          $tab = new Tab();
//          $team_details = new Table(['Topic', 'Team A','Team B'], [
//            [
//              'Name',
//              $model->fteam->details->name,
//              $model->steam->details->name
//            ],
//          ]);
//          $players_usernames = [];
//          $X = 0;
//          $Y = 0;
//          foreach ($model->bets_participants as $participant) {
//            if($Y == 5) {
//              $Y = 0;
//              $X++;
//            }
//            $players_usernames[$X][$Y] = $participant;
//            $Y++;
//          }
//          $players = new Table(['Users'],$players_usernames);
//          $tab->add("Teams", $team_details);
//          $tab->add("Players", new Box("Players", $players));
//          return new Box("Game description", $tab);
//        });
        $grid->column('banner', __('Banner'))->image();
        $grid->column('total_bet_amount', __('Total bet pot'));
        $grid->column('total_bets', __('Total bets'));
        $grid->column('start_time', __('Start time'))->date(config('constants.date_format'));
        $grid->column('end_time', __('End time'))->date(config('constants.date_format'));
        $grid->column('background_image', __('Background image'))->image();

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
        $show = new Show(BettingGroup::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('betting_category_id', __('Betting category id'));
        $show->field('title', __('Title'));
        $show->field('banner', __('Banner'));
        $show->field('description', __('Description'));
        $show->field('max_amount', __('Max amount'));
        $show->field('match_duration', __('Match duration'));
        $show->field('start time', __('Start time'));
        $show->field('end_time', __('End time'));
        $show->field('background_image', __('Background image'));
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
        $form = new Form(new BettingGroup());

        $form->select('betting_category_id', __('Game category'))->options(BettingCategory::all()->pluck('name','id'))->required();
        $form->text('title', __('Game title'))->required();
        $form->image('banner', __('Banner'))->required();
        $form->textarea('description', __('Description'))->required();
        $form->decimal('max_amount', __('Max amount'))->required();
        $form->text('match_duration', __('Match duration'))->required();
        $form->datetime('start_time', __('Bet start time'))->required();
        $form->datetime('end_time', __('Bet end time'))->required();
        $form->image('background_image', __('Background image'))->required();
        if(Admin::user()->isAdministrator()) {
          $states = [
            'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
          ];
          $form->switch('resolved','Resolved?')->states($states);
        }
        $form->hasMany('teams', __('Teams'), function (Form\NestedForm $form) {
          $form->select('team_id',"Team name")->options(BettingTeam::all()->pluck('name','id'))->required();
        });

        return $form;
    }
}
