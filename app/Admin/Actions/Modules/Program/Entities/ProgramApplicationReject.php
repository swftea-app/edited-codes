<?php

namespace App\Admin\Actions\Modules\Program\Entities;

use Carbon\Carbon;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Jobs\NotificationJob;

class ProgramApplicationReject extends RowAction
{
    public $name = 'Reject Application';

  public function dialog()
  {
    $this->confirm('Are you sure to reject this application?');
  }
    public function handle(Model $model) {

      if($model->resolved) {
        return $this->response()->error('This ticket is already resolved.')->refresh();
      }

      $sender_id = $model->user_id;

      $sender = $model->user;
      $under = $model->under;
      $type = $model->type;
      # 1 Calculate all variables
      $program_will_expire_in = Carbon::now();
      $reward = 0;
      $role_name = "";
      if($type == 'merchantship') {
        $role_name = "User";
        $reward = config('program.amount_to_be_merchant');
      }
      if($type == 'mentorship') {
        $role_name = "Merchant";
        $reward = config('program.amount_to_be_mentor');
      }
      # Clear old data
      DB::table('users')->select(['tag_id'])->where('tag_id',"=", $sender_id)->update([
        'tag_id' => 1
      ]);
      DB::table('users')->select(['tag_id'])->where('id',"=", $sender_id)->update([
        'program_expiry' => $program_will_expire_in,
        'program_point' => 0
      ]);

      # All good?
      $model->resolved = true;
      $model->save();

      $application = \Modules\Program\Entities\ProgramApplication::where("id","=",$model->application_id)->first();
      $application->status_message = "Rejected by Administrators!!";
      $application->status = 0;
      $application->save();

      # Add credit
      $under->histories()->create([
        'type' => 'Transfer',
        'creditor' => 'system',
        'creditor_id' => 1,
        'message' =>  "Refunded ".$reward." NPR from rejection of ".$application->type." program of ".$sender->username,
        'old_value' => $under->credit,
        'new_value' => $under->credit + $reward,
        'user_id' => $under->id
      ]);
      DB::table('users')
        ->where('id','=',$under->id)
        ->increment('credit', $reward);

      dispatch(new NotificationJob('promotion_executed_rejected', $model));


      # Add color
      if(!empty($role_name)) {
//        Artisan::call("chatmini:user", [
//          'op' => 'update',
//          '--user' => $sender_id,
//          '--roles' => $role_name,
//        ]);
      }
      return $this->response()->success('Success.')->refresh();

    }

}