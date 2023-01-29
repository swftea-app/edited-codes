<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->resource('users', UserController::class);
    $router->resource('emotion-categories', EmoticonsCategoryController::class);
    $router->resource('emoticons', EmoticonController::class);
    $router->resource('all-gifts', GiftController::class);
    $router->resource('announcements', AnnouncementController::class);
    $router->resource('new-promotions', NewApplicationController::class);
    $router->resource('pages', CustomPageController::class);
    $router->resource('admin-transactions', TransactionController::class);
    $router->resource('account-histories', HistoryTracker::class);
    $router->resource('badges', BadgeController::class);
    $router->resource('avatar-keys', AvatarKeyController::class);
    $router->resource('avatar-items', AvatarItemController::class);
    $router->resource('betting-categories', BettingCategoryController::class);
    $router->resource('betting-groups', BettingGroupController::class);
    $router->resource('betting-teams', BettingTeamController::class);
    $router->resource('mission-seasons', MissionSeasonController::class);
    $router->resource('mission-tasks', MissionTaskController::class);
    $router->resource('mission-weeks', MissionWeekController::class);
    $router->resource('swftea-contests', SwfteaContestController::class);
});
