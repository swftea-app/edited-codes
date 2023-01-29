<?php

namespace App\Admin\Actions\Modules\Program\Entities;

use Carbon\Carbon;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\Program\Entities\MerchantTag;
use Modules\Program\Jobs\ProgramJob;
use Modules\SwfteaMission\Jobs\SeasonPoint;

class ProgramApplication extends RowAction
{
    public $name = 'Accept Application';
    public $selector = '.accept_application';

  public function dialog()
  {
    $this->confirm('Are you sure to accept this application?');
  }
  public function handle(Model $model) {

    if($model->resolved) {
      return $this->response()->error('This ticket is already resolved.')->refresh();
    }

      $under_of_id = $model->under_of;
      $sender_id = $model->user_id;

      $sender = $model->user;
      $type = $model->type;
      $role_name = "";
      # 1 Calculate all variables
      $program_will_expire_in = Carbon::now()->addDays(30);
      $reward = 0;
      if($type == 'merchantship') {
        $role_name = "Merchant";
        $reward = config('program.amount_to_be_merchant');
        dispatch(
          new SeasonPoint(
            'add points',
            $sender->id,
            'System',
            'become_merchant',
            1)
        )->onQueue('low');
      }
      if($type == 'mentorship') {
        $role_name = "Mentor";
        $reward = config('program.amount_to_be_mentor');
      }
      # Clear old data
      DB::table('users')->select(['tag_id'])->where('tag_id',"=", $sender_id)->update([
        'tag_id' => 1
      ]);
      $tag= new MerchantTag();
      $tag->user_of = $under_of_id;
      $tag->user_id = $sender_id;
      $tag->expire_at = $program_will_expire_in;
      $tag->save();
      DB::table('users')->select(['tag_id'])->where('id',"=", $sender_id)->update([
        'tag_id' => $under_of_id,
        'program_expiry' => $program_will_expire_in
      ]);

      # All good?
      $model->resolved = true;
      $model->save();

      $application = \Modules\Program\Entities\ProgramApplication::where("id","=",$model->application_id)->first();
      $application->status_message = "Request Executed!!";
      $application->status = 3;
      $application->save();

      # Add credit# Add credit
      $sender->histories()->create([
        'type' => 'Transfer',
        'creditor' => 'system',
        'creditor_id' => 1,
        'message' =>  "Received ".$reward." NPR as reward from ".$application->type." program.",
        'old_value' => $sender->credit,
        'new_value' => $sender->credit + $reward,
        'user_id' => $sender->id
      ]);
      DB::table('users')
        ->where('id','=',$sender->id)
        ->increment('credit', $reward);
      $sender->program_point = 0;
      $sender->save();

      # Add color
      if(!empty($role_name)) {
        Artisan::call("chatmini:user", [
          'op' => 'update',
          '--user' => $sender_id,
          '--roles' => $role_name,
        ]);
      }
      dispatch(new NotificationJob('promotion_executed_accepted', $model));
      return $this->response()->success('Success.')->refresh();

    }

}