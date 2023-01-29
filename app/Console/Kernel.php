<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Jobs\NotificationJob;
use function foo\func;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
          $schedule->command('chatmini:server refresh announcements')->everyThirtyMinutes();
          $schedule->command('chatmini:server refresh sync_gifts')->onSuccess(function () {
            dispatch(new NotificationJob('admin_info_notification', (object) [
              'title' => 'Server Notice',
              'message' => 'Gifts synced. Thank you'
            ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
          })
            ->timezone('Asia/Kathmandu')
            ->dailyAt("23:45");
//          $schedule->command('chatmini:server refresh contest')->everyFifteenMinutes();
      /**
       *  Kicked list
       */
          $schedule->command('chatmini:server refresh kicked_list')->onSuccess(function () {
//            dispatch(new NotificationJob('admin_info_notification', (object) [
//              'title' => 'Server Notice',
//              'message' => 'Kicked list refreshed. Thank you'
//            ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
          })->onFailure(function () {
            dispatch(new NotificationJob('admin_info_notification', (object) [
              'title' => 'Kicked list refresh failure',
              'message' => 'Refresh failed. Let know this incident to admin.'
            ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
          })->everyThirtyMinutes();
      /**
       * Picker Code
       */
        $schedule->command('chatmini:server refresh picker')->everyFifteenMinutes();
      /**
       * Refresh colors of merchant and mentors
       */
        $schedule->command('chatmini:server refresh merchants')->timezone('Asia/Kathmandu')->dailyAt('01:00');
        $schedule->command('chatmini:server refresh mentors')->timezone('Asia/Kathmandu')->dailyAt('01:05');
        $schedule->command('chatmini:server refresh tags')->timezone('Asia/Kathmandu')->dailyAt('01:10');
      /**
       * Online users
       */
      $schedule->command('chatmini:server refresh users')->everyFiveMinutes();
      /**
       * Kicked list
       */
      /**
       * Trail system
       */
      $schedule->command('chatmini:server refresh trails')->onSuccess(function () {
        dispatch(new NotificationJob('admin_info_notification', (object) [
          'title' => 'Server Notice',
          'message' => 'Trails provided to all users.'
        ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
      })->onFailure(function () {
        dispatch(new NotificationJob('admin_info_notification', (object) [
          'title' => 'Trail system error',
          'message' => 'Refresh failed. Let know this incident to admin.'
        ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
      })->timezone('Asia/Kathmandu')->dailyAt("23:45");

      $schedule->command('chatmini:server refresh credit_transfer')->timezone('Asia/Kathmandu')->dailyAt("23:45");

//      $schedule->command('backup:run --only-db')
//            ->timezone('Asia/Kathmandu')
//            ->dailyAt('00:00')->onSuccess(function () use ($schedule) {
//              dispatch(new NotificationJob('admin_info_notification', (object) [
//                'title' => 'Server Info',
//                'message' => 'Backup successful. Database backup to google drive.'
//              ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
//              Artisan::call('chatmini:server', [
//                'action' => 'refresh',
//                'op' => 'messages',
//              ]);
//            })->onFailure(function () {
//              dispatch(new NotificationJob('admin_info_notification', (object) [
//                'title' => 'Backup failure',
//                'message' => 'Backup failed. Let know this incident to admin.'
//              ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
//            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
