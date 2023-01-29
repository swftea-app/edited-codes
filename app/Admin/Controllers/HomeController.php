<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Chatroom\Entities\Chatroom;
use Modules\Games\Entities\Game;
use Modules\UserSystem\Entities\OnlineUsers;
use Modules\UserSystem\Entities\User;

class HomeController extends Controller
{
    public function index(Content $content) {
        return $content
            ->title('Dashboard')
            ->row(function (Row $row) {
              $online_users = OnlineUsers::all()->count();
              $users = User::all()->count();
              $presenceBlock = new Box('Online users');
              $presenceBlock->content($online_users.'/'.$users);
              $row->column(2, $presenceBlock);
              $running_games = Game::where('phase','>',0)->get()->count();
              $total_games = Game::all()->count();
              $activeGameBlock = new Box('Running games');
              $activeGameBlock->content($running_games.'/'.$total_games);
              $row->column(2, $activeGameBlock);
              $chatrooms = Chatroom::all()->count();
              $chatroomBlock = new Box('Chatrooms');
              $chatroomBlock->content($chatrooms);
              $row->column(2, $chatroomBlock);
            })
            ->description('Description...');
    }
}
