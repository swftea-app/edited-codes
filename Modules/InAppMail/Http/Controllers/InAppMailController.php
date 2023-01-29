<?php

namespace Modules\InAppMail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\InAppMail\Emails\AppMail;
use Modules\InAppMail\Entities\ReceivedAppMail;
use Modules\InAppMail\Entities\SentAppMail;
use Modules\InAppMail\Jobs\InappMailJob;
use Modules\UserSystem\Entities\User;

class InAppMailController extends Controller {
   public function sendEmail(Request $request) {
     $validator = Validator::make($request->all(), [
       'to' => 'required',
       'subject' => 'required|min:2',
       'body' => 'required|min:5',
     ]);
     if($validator->fails()) {
       return [
         "error" => true,
         "message" => $validator->errors()->first()
       ];
     }

     $sender_username = auth()->user()->username;
     $receiver_username = $request->to;

     # validate receiver
     $receiver = User::where('username','=', $receiver_username)->first();
     if(!$receiver) {
       return [
         "error" => true,
         "message" => "Receiver not found."
       ];
     }
     $can_send = false;

     if(!$can_send && $receiver->can('send email to any user')) {
       $can_send = true;
     }
     if(!$can_send && $receiver->profile->receiver_email_from_all_users) {
       $can_send = true;
     }
     if(!$can_send && $receiver->isFriendWith(auth()->user())) {
       $can_send = true;
     }
     if(!$can_send) {
       return [
         "error" => true,
         "message" => $receiver_username." cannot receive emails at the moment."
       ];
     }

     // Email
     $sender_email = $sender_username.'@swftea.com';
     $receiver_email = $receiver_username.'@swftea.com';
     $subject = $request->subject;
     $message = $request->body;

     $sentMail = new SentAppMail();
     $sentMail->title = 'In App Mail';
     $sentMail->subject = $subject;
     $sentMail->body = $message;
     $sentMail->sender_id = auth()->user()->id;
     $sentMail->receiver_id = $receiver->id;
     $sentMail->save();

     dispatch(new InappMailJob("sendMail", [
       'sender' => $sender_email,
       'sender_name' => auth()->user()->name,
       'subject' => $subject,
       'body' => $message,
       'receiver' => $receiver_email,
     ]))->onQueue('low');
     return [
       'error' => false,
       'message' => 'Mail sent..'
     ];
   }
   public function inbox() {
     $inboxs = ReceivedAppMail::where('receiver_id','=',auth()->user()->id)->where('deleted','=', false)->orderBy('id','DESC')->paginate(25);
     foreach ($inboxs as $key => $inbox) {
       $inboxs[$key]->created_on = Carbon::parse($inbox->created_at)->diffForHumans();
       if($inbox->sender_id != 0) {
         $sender = DB::table('users')->select('picture')->where('id','=',$inbox->sender_id)->first();
         if($sender) {
           $inboxs[$key]->picture = $sender->picture;
         } else {
           $inboxs[$key]->picture = NULL;
         }
       } else {
         $inboxs[$key]->picture = NULL;
       }
     }
     return $inboxs;
   }
   public function sentEmail() {
     $sent_mails = SentAppMail::where('sender_id','=',auth()->user()->id)->with(['receiver'])->where('deleted','=', false)->orderBy('id','DESC')->paginate(25);
     foreach ($sent_mails as $key => $inbox) {
       $sent_mails[$key]->created_on = Carbon::parse($inbox->created_at)->diffForHumans();
     }
     return $sent_mails;
   }
   public function read($id) {
     $mail = ReceivedAppMail::where('id','=',$id)->where('receiver_id','=',auth()->user()->id)->where('deleted','=', false)->first();
     if(!$mail) {
       return [
         "error" => true,
         "message" => "Error"
       ];
     }
     $mail->seen = true;
     $mail->save();
     return $mail;
   }
   public function delete($id) {
     $mail = ReceivedAppMail::where('id','=',$id)->where('receiver_id','=',auth()->user()->id)->first();
     if(!$mail) {
       return [
         "error" => true,
         "message" => "Error"
       ];
     }
     $mail->deleted = true;
     $mail->save();
     return [
       "error" => false,
       "message" => "Deleted."
     ];
   }

   public function deleteSent($id) {
     $mail = SentAppMail::where('id','=',$id)->where('sender_id','=',auth()->user()->id)->first();
     if(!$mail) {
       return [
         "error" => true,
         "message" => "Error"
       ];
     }
     $mail->deleted = true;
     $mail->save();
     return [
       "error" => false,
       "message" => "Deleted."
     ];
   }
}
