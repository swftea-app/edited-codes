<?php
namespace Modules\InAppMail;

use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Support\Facades\DB;
use Modules\InAppMail\Entities\ReceivedAppMail;
use Modules\UserSystem\Entities\User;

class InAppMailHandler {
    public function __invoke(InboundEmail $email, $username) {

      $receivers = $email->to();
      foreach ($receivers as $receiver) {
        $explode_receiver = explode('@', $receiver);
        if(end($explode_receiver) == 'swftea.com') {
         $receiver_username = $explode_receiver[0];
         $rec = User::where('username','=',$receiver_username)->with('profile')->first();
         if($rec) {
           $can_receive = false;
           $internal_sender_id = 0;
           if(!$can_receive && $rec->profile->receiver_email_from_all_users) {
             $can_receive = true;
           }
           # internal sender
           $sender_explode = explode("@", is_array($email->from()) ? implode(",", $email->from()) : $email->from());
           if(count($sender_explode) > 1 && $sender_explode[1] == 'swftea.com') {
             $sender_username = $sender_explode[0];
             $sender = DB::table('users')->select('id')->where('username','=', $sender_username)->where('status','=',1)->first();
             if($sender) {
               $internal_sender_id = $sender->id;
             }
           }
           if($internal_sender_id != 0) { // sent by valid internal user
             $sender = User::where('id','=', $internal_sender_id)->first();
             if($rec->isFriendWith($sender)) {
               $can_receive = true;
             }
           } else {
             $can_receive = true;
           }

           if($can_receive) {
             ReceivedAppMail::create([
               'sender'    => is_array($email->from()) ? implode(",", $email->from()) : $email->from(),
               'subject'   => is_array($email->subject()) ? implode(",", $email->subject()):$email->subject(),
               'receiver'  => $receiver,
               'body'      => is_array($email->text())?implode(",", $email->text()):$email->text(),
               'additional_data' => [
                 'from_name' => is_array($email->fromName())?implode(",", $email->fromName()):$email->fromName()
               ],
               'receiver_id' => $rec->id,
               'sender_id' => $internal_sender_id,
             ]);
           }
         }
        }
      }
    }
}