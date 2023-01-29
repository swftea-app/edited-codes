<?php

namespace Modules\AccountHistory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AccountHistory\Events\SendNotification;

class AccountHistoryJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


  public $model;
  public function __construct($model) {
    $this->model = $model;
  }
  public function handle() {
    switch ($this->type) {
      case 'gift':
        event(new SendNotification($this->model));
        break;
    }
  }
}
