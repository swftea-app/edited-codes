<?php

namespace Modules\GroupChat\Http\Controllers;

use App\Events\MessageWasComposed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Participant;
use Lexx\ChatMessenger\Models\Thread;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Gift\Entities\AllGifts;
use Modules\GroupChat\Events\NewMemberAdded;
use Modules\GroupChat\Events\NewMessageSent;
use Modules\GroupChat\Events\UpdateGroup;
use Modules\TextParser\Parser;
use Modules\UserSystem\Entities\User;

class GroupChatController extends Controller {
   public function sendMessage(Request $request) {
     $validator = Validator::make($request->all(), [
       'slug' => 'required',
       'message' => 'required',
       'images.*' => 'mimes:jpeg,jpg,png,gif|max:10000'
     ]);
     if($validator->fails()) {
       return [
         "error" => true,
         "message" => $validator->errors()->first(),
       ];
     }
     $thread_id = $request->slug;
     $message_text = $request->message;
     $thread = Thread::where('slug','=',$thread_id)->first();
     if(!$thread) {
       # first time chat
       $thread = Thread::create([
         "subject" => "Private chat",
         "slug" => $thread_id
       ]);
       $thread->mode = "private";
       $thread->save();
     }
     $parser = new Parser($message_text, auth()->user());
     $parser->parseEmoticons();
     $parser->type = 'group';
     # sender
     $this->addMemberToThread($thread->id, auth()->user()->id);
     # recipients
     if ($request->has('recipients')) {
       $receiver = $request->recipients;
       $added = $this->addMemberToThread($thread->id, $receiver, [
         'message' => $message_text,
         'extra_info' => [
           'emojies' => $parser->emojies
         ],
         'sender' => [
           'username' => auth()->user()->username,
           'color' => auth()->user()->color
         ]
       ]);
       if($added['error']) {
         event(new InfoCommand($added['message'], auth()->user()->id,'thread',$thread->slug));
         return;
       }
     }

     # parsing
     $sender = auth()->user();
     $message_group = "message";
     if($parser->command_found) {
       switch ($parser->command) {
         case 'gift':
           if($thread->mode == "private") {
             if($parser->whom == "all") {
               event(new InfoCommand('You cannot shower in private chat.', $parser->sender->id,'thread',$thread->slug));
               return;
             }
           }
           $gift_name = $parser->name;
           $receivers = collect([]);
           $gift = AllGifts::where('name','=',$gift_name)->first();
           if($gift) {
             $parser->valid_name = true;
           } else {
             event(new InfoCommand("The selected gift '".$gift_name."' is not listed in our store. Please visit gift store for more information.", $parser->sender->id,'thread',$thread->slug));
             return;
           }
           $parser->gift = $gift;
           if($parser->gift->discount > 0) {
             $parser->gift->price = $parser->gift->price - (($parser->gift->discount/100) * $parser->gift->price);
           }
           // Check if gift exists
           if (!$parser->valid_name) {
             event(new InfoCommand("The selected gift '".$gift_name."' is not listed in our store. Please visit gift store for more information.", $parser->sender->id,'thread',$thread->slug));
             return;
           }
           // Validate and grab receivers
           if ($parser->whom == 'all') {
             foreach ($thread->participantsUserIds() as $member) {
               $receiver = User::where("id","=",$member)->first();
               # Exclude sender
               if($receiver->id != $parser->sender->id) {
                 $receivers->push($receiver);
               }
             }
             $parser->receivers = $receivers;
             if(count($parser->receivers) < 2) {
               event(new InfoCommand("Gift shower failed. Unable to shower as it required minimum of 3 users.", $parser->sender->id,'thread',$thread->slug));
               return;
             }
             $message_group = $parser->gift->price > 2 ? "gift_all" : "gift_all_cheap";
             $sender_history_message = $parser->gift->name . " sent to ".count($receivers)." users of group with rate of ".$parser->gift->price." credits";
           } else {
             $receiver = User::where('username', '=', $parser->whom)->first();
             if (!$receiver) {
               event(new InfoCommand("Gift sending failed. Please recheck username and try again.", $parser->sender->id,'thread',$thread->slug));
               return;
             } # Single receiver not existed
             if ($parser->gift->price < 2.20) {
               event(new InfoCommand("Gift sending failed. Gift with price below 2.20 credits can't be sent in private.", $parser->sender->id,'thread',$thread->slug));
               return;
             } # Single receiver not existed
             $parser->receiver = $receiver;
             $receivers->push($receiver);
             $message_group = "gift";
             $sender_history_message = $parser->gift->name . " sent to " . $receiver->username . " with rate of ".$parser->gift->price." credits";
           }
           // Grab total credit
           $total_credit_used = count($receivers) * $parser->gift->price;
           // Validate balance
           if ($parser->sender->credit < $total_credit_used) {
             event(new InfoCommand("Gift sending failed. Insufficient balance for sending gifts. Please contact your nearest merchant/mentor or visit our store for purchasing credits. Thank you.", $parser->sender->id,'thread',$thread->slug));
             return;
           }
           // Validate receivers
           if (count($receivers) == 0) {
             event(new InfoCommand("There are no sufficient users in this chatroom.", $parser->sender->id,'thread',$thread->slug));
             return;
           }
           // All Good?
           # Parse text
           $parser->parseGift();
           # Add account history to sender
           $parser->sender->histories()->create([
             'type' => 'gift',
             'creditor' => 'thread',
             'creditor_id' => $thread->id,
             'message' => $sender_history_message,
             'old_value' => $parser->sender->credit,
             'new_value' => $parser->sender->credit - $total_credit_used,
             'user_id' => $parser->sender->id
           ]);
           # Deduct amount from sender
           DB::table('users')
             ->where('id','=',$parser->sender->id)
             ->decrement('credit', $total_credit_used);
           # Send info message
           if(count($receivers) > 1) {
             event(new InfoCommand('Wow!! You have sent a '.$gift_name.' to '.count($receivers).' users using '.$total_credit_used.' credits. Thank you.',$parser->sender->id,'thread',$thread->slug));
           } else {
             event(new InfoCommand('Wow!! You have sent a '.$gift_name.' to '.$parser->receiver->username.' using '.$total_credit_used.' credits. Thank you.',$parser->sender->id,'thread',$thread->slug));
           }
           $parser->gift->gift_image = getImageUrl($parser->gift->gift_image);
           # Add gift to user
           foreach ($receivers as $receiver) {
             $receiver->gifts()->create([
               'name' => $parser->gift->name,
               'price' => $parser->gift->price,
               'icon' => '-',
               'key' => '-',
               'discount' => $parser->gift->discount,
               'user_id' => $parser->sender->id,
               'receiver_id' => $receiver->id,
               'type_id' => $thread->id,
               'type' => 'thread',
               'gift_url' => asset($parser->gift->gift_image)
             ]);
           }
           break;
         case 'add':
         case 'invite':
           $whom = User::where('username','=', $parser->whom)->first();
           if(!$whom) {
             event(new InfoCommand("User not found.", $sender->id,'thread',$thread->slug));
             return;
           }
           if($whom->pres != 'online') {
             event(new InfoCommand("User is offline.", $sender->id,'thread',$thread->slug));
             return;
           }
           if($thread->hasParticipant($whom->id)) {
             event(new InfoCommand("User is already in chat.", $sender->id,'thread',$thread->slug));
             return;
           }
           if($thread->mode == 'private') {
             $slug = uniqid();
             // Create new group
             $new_thread = Thread::create([
               'subject' => 'Group Chat',
               'slug' => $slug
             ]);
             $new_thread->mode = "group";
             $new_thread->slug = $slug;
             $new_thread->subject = "Group Chat";
             $new_thread->save();
             // Old members
             $old_members = $thread->participantsUserIds();
             $all_members = [];
             foreach ($old_members as $member) {
               $all_members[] = $member;
             }
             $all_members[] = $whom->id;
             $new_thread->addParticipant($all_members);
             // Title and label
             $members = DB::table('users')->select(['username'])->whereIn('id', $all_members)->get();
             $users = [];
             foreach ($members as $member) {
               $users[] = $member->username;
             }
             if($thread->subject == "Group Chat") {
               $title = natural_language_join($users);
             } else {
               $title = $thread->title;
             }
             $thread->save();
             $label = "Group Chat";

             $extra_info = [];
             $extra_info['members'] = implode(", ", $users);
             $extra_info['invited_by'] = auth()->user()->username;
             foreach ($all_members as $member) {
               broadcast(new NewMemberAdded($member, $new_thread->slug, $title, null, $label, $extra_info));
             }
             return [
               "error" => false
             ];
           } else {
             $thread->addParticipant([$whom->id]);

             $title = $thread->subject;
             $label = "Group Chat";
             $members = [];
             $users = DB::table('private_public_messages_participants')->select(['user_id'])->where('thread_id','=', $thread->id)->get();
             foreach ($users as $user) {
               $u = DB::table('users')->select(['username'])->where('id','=', $user->user_id)->first();
               $members[] = $u->username;
             }
             $extra_info['members'] = implode(", ", $members);
             $extra_info['invited_by'] = auth()->user()->username;
             broadcast(new NewMemberAdded($whom->id, $thread->slug, $title, null, $label, $extra_info));
             $parser->parseGroupJoin();
             $data = [
               'sender' => auth()->user(),
               'formatted_text' => $parser->formatted_text,
               'type' => "info"
             ];
             event(new NewMessageSent($data, $thread->slug));
             return;
           }
           break;
         case 'list':
           $members = $thread->participantsUserIds();
           $members = DB::table('users')->select(['username'])->whereIn('id', $members)->get();
           $users = [];
           foreach ($members as $member) {
             $users[] = $member->username;
           }
           event(new InfoCommand("Currently in this group: ".implode(", ", $users),$parser->sender->id,"thread", $thread->slug, "info"));
           return;
           break;
         case 'title':
           if($thread->mode == "private") {
             event(new InfoCommand("Private chats title cannot be changed.", $sender->id,'thread',$thread->slug));
           } else {
             if($thread->creator()->id == $sender->id) {
               $thread->subject = $parser->without_command_text;
               $thread->save();
               broadcast(new UpdateGroup(['name' => $thread->subject], $thread->slug));
               $data = [
                 'sender' => auth()->user(),
                 'formatted_text' => $sender->username." changed the title of this group.",
                 'type' => "info"
               ];
               event(new NewMessageSent($data, $thread->slug));
             } else {
               event(new InfoCommand("Only {$thread->creator()->username} can change title.", $sender->id,'thread',$thread->slug));
             }
           }
           return;
           break;
         default:
           event(new InfoCommand("Command not found.", $sender->id,'thread',$thread->slug));
           return [
             "error" => true,
             "message" => "Command not found"
           ];
       }
     }
     $parser->parseEmoticons();
     # create message
     Message::create([
       'thread_id' => $thread->id,
       'user_id' => auth()->user()->id,
       'type' => $message_group,
       'body' => $parser->formatted_text,
     ]);
     $images = [];
     if($request->hasFile('images')) {
       foreach($request->file('images') as $file) {
         $f = Storage::disk('public')->putFile('private_chat_images', $file);
         $url = asset(Storage::disk('public')->url($f));
         $images[] = [
           'path' => $url
         ];
       }
     }
     $data = [
       'sender' => auth()->user(),
       'formatted_text' => $parser->formatted_text,
       'extra_info' => [
         'emojies' => $parser->emojies,
         'images' => $images
       ],
       'type' => $message_group
     ];
     event(new NewMessageSent($data, $thread->slug));
     return [
       "error" => false,
       "message" => "Chat added."
     ];
   }

   public function leave($slug) {
     $thread = Thread::where("slug","=", $slug)->first();
     if($thread) {
       if($thread->hasParticipant(auth()->user()->id)) {
         if($thread->mode == 'private') {
//           $left_message = auth()->user()->username.' is offline';
         } else {
           $left_message = auth()->user()->username.'['.auth()->user()->level->value.'] left this group';
           $data = [
             'sender' => auth()->user(),
             'formatted_text' => $left_message,
             'type' => 'info'
           ];
           DB::table('private_public_messages_participants')->where('user_id','=',auth()->user()->id)->delete();
           event(new NewMessageSent($data, $thread->slug));
         }
       }
       return [
         "error" => false,
         "message" => "Left group"
       ];
     }
   }

   public function addMemberToThread($thread_id, $user_id, $message = null) {
     $thread = Thread::where("id","=",$thread_id)->first();
     if(!$thread) {
       return [
         "error" => true,
         "message" => "Invalid thread"
       ];
     }
     $user = DB::table('users')->select(['pres','username'])->where('id','=', $user_id)->first();
     if($user->pres != "online") {
       return [
         "error" => true,
         "message" => $user->username." is offline."
       ];
     }
     $members = [];
     if(!$thread->hasParticipant($user_id)) {
       $thread->addParticipant([$user_id]);
       if(auth()->user()->id != $user_id) {
         $extra_info = null;
         if($thread->mode == 'private') {
           $title = auth()->user()->username;
           $label = 'Private Chat';
         } else {
           $title = $thread->subject;
           $label = 'Group Chat';
           $members[] = $user->username;
           $users = DB::table('private_public_messages_participants')->select(['user_id'])->where('thread_id','=', $thread->id)->get();
           foreach ($users as $user) {
             $u = DB::table('users')->select(['username'])->where('id','=', $user->user_id)->first();
             $members[] = $u->username;
           }
           $extra_info['members'] = implode(", ", $members);
           $extra_info['invited_by'] = auth()->user()->username;
         }
         broadcast(new NewMemberAdded($user_id, $thread->slug, $title, $message, $label, $extra_info));
       }
     }

     return [
       "error" => false,
       "message" => "Member added."
     ];
   }

   public function updateThread($thread_id) {
     $thread = Thread::where("id","=", $thread_id)->first();
     if($thread) {
       return Thread::create([
         "subject" => "Group chat",
         "slug" => uniqid()
       ]);
     }
   }

   /**
    * NEW
    */
   public function initPrivateChat($from, $to) {
     $from = intval($from);
     $to = intval($to);
     $thread_id = getThreadId($from, $to);
     $thread = Thread::firstOrNew([
       "subject" => "Private chat",
       "slug" => $thread_id
     ]);
     $thread->mode = "private";
     $thread->save();
     // INIT ED
     $receiver = DB::table('users')->select(['username','pres'])->where('id','=',$to)->first();
     if(!$thread->hasParticipant($from)) {
       $thread->addParticipant([$from]);
     }
     if(!$thread->hasParticipant($to)) {
       $thread->addParticipant([$to]);
     }
     $response = [
       'thread_id' => $thread_id,
       'title' => $receiver->username,
       'type' => 'private',
       'presence' => $receiver->pres,
     ];
     return $response;
   }

}
