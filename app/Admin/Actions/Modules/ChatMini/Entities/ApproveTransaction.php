<?php

namespace App\Admin\Actions\Modules\ChatMini\Entities;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\UserSystem\Entities\User;

class ApproveTransaction extends RowAction {
    public $name = 'Approve';
    public function handle(Model $model, Request $request){
      $amount = $request->get('amount');
      $requester = \Encore\Admin\Facades\Admin::user();
      $username = $request->get('username');
      $user = User::where('username','=',$username)->first();
      if($amount < 10) {
        $model->comments()->create([
          'comment' => 'Sorry amount needs to more than 10. Requested '.$amount.'. Processed by '.$requester->name,
          'commentor' => 'System'
        ]);
        return $this->response()->error('Sorry amount needs to more than 0.')->refresh();
      } else if($model->flagged) {
        $model->comments()->create([
          'comment' => 'Processed done ticket by '.$requester->name,
          'commentor' => 'System'
        ]);
        return $this->response()->error('This ticket is done.')->refresh();
      } else if(!$user) {
        $model->comments()->create([
          'comment' => 'Invalid '.$username.' processed by '.$requester->name,
          'commentor' => 'System'
        ]);
        return $this->response()->error('Invalid username.')->refresh();
      } else {
        $model->comments()->create([
          'comment' => 'Processed '.$amount.' credit by '.$requester->name.' for '.$username,
          'commentor' => 'System'
        ]);
        $model->flagged = true;
        $model->save();

        # All good

        $old_credit = $user->credit;
        $new_credit = $old_credit + $amount;
        $user->credit = $new_credit;
        $model->comments()->create([
          'comment' => 'Amount transferred to '.$username.'. New balance is '.$new_credit,
          'commentor' => 'System'
        ]);
        $user->save();
        $user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'system',
          'creditor_id' => 1,
          'message' =>  "Received ".$amount." credit from system for ticket no: ".$model->id.'. Thanks.',
          'old_value' => $old_credit,
          'new_value' => $new_credit,
          'user_id' => $user->id
        ]);
        return $this->response()->success('Processed')->refresh();
      }
    }
    public function form() {
      $this->text('username','Username (App)')->rules('required|min:3');
      $this->text('amount','Amount')->rules('required|min:1');
    }
}