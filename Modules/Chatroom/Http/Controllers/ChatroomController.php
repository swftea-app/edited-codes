<?php

namespace Modules\Chatroom\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Chatroom\Entities\Chatroom;
use Modules\Chatroom\Jobs\ChatroomMessage;
use Modules\UserSystem\Entities\User;

class ChatroomController extends Controller {
  private $valid_chatroom_type = ['official','trending', 'gaming','own', 'favourites', 'recent'];
  private $valid_info_types = ['room_info','balance','participants','kick_list'];
    public function joinRoom($room_id) {
      try {
        $user = auth()->user();
        $chatroom = Chatroom::with(['blockedMembers','kickedMembers','members','user','game'])->withCount(['members'])->where('id','=', $room_id)->first();
        if($chatroom->members->contains(auth()->user()->id)) {
          return \response([
            'error' => false,
            'chatroom' => $chatroom
          ]);
        } // already exist no worries
        $access = false;
        if(!$access && $user->id == config('usersystem.super_admin_uid')) {
          $access = true;
        } // Super Admin
        if(!$access && $user->can('join any chatroom')) {
          $access = true;
        } // has permission [global]
        if(!$access && $user->can('join any locked chatroom')) {
          $access = true;
        } // has permission [global]
        if(!$access && $chatroom->user_id == $user->id) {
          $access = true;
        } // own room
        if(!$access && $chatroom->moderators->contains($user->id)) {
          $access = true;
        } // is moderator

        # Normal user
        $cannot_join_message = '';
        if(!$access) {
          $normal_join = true;
          if($normal_join && $chatroom->members_count >= $chatroom->capacity) {
            $normal_join = false;
            $cannot_join_message = "Chatroom is full. Try after some moments";
          }
          if($normal_join && $chatroom->blockedMembers->contains($user->id)) {
            $normal_join = false;
            $cannot_join_message = "You are banned from this chatroom. Please contact chatroom administrators.";
          }
          if($normal_join && $chatroom->kickedMembers->contains($user->id)) {
            $normal_join = false;
            $cannot_join_message = "You are recently kicked from this chatroom. You can rejoin after 30 minutes.";
          }
          if($normal_join && $chatroom->locked) {
            $normal_join = false;
            $cannot_join_message = "This chatroom is currently locked. Try again later.";
          }
          if($normal_join) {
            $access = true;
          }
        }
        if(!$access) {
          return [
            "error" => true,
            "message" => isset($cannot_join_message) ? $cannot_join_message : "Internal server error!!"
          ];
        }
        ## Everything good?
        Artisan::call("chatmini:chatroom", [
          'op' => 'join',
          '--user' => $user->id,
          '--id' => $room_id,
        ]);
        $chatroom->recentUser()->detach($user->id);
        $chatroom->recentUser()->attach($user->id);

        #get raw chatroom
        $members = [];
        foreach ($chatroom->members as $member) {
          $members[] = (object) [
            'username' => $member->username
          ];
        }
        $creator = (object) [
          'username' => $chatroom->user->username
        ];
        $c = (object) [
          'name' => $chatroom->name,
          'id' => $chatroom->id,
          'description' => $chatroom->description,
          'announcement' => $chatroom->announcement,
          'members' => $members,
          'game' => $chatroom->game,
          'user' => $creator,
        ];
        return \response([
          'error' => false,
          'chatroom' => $c
        ]);
      } catch (\Exception $exception) {
       return \response([
          'error' => true,
          'message' => "Internal server error. ".$exception->getMessage(),
        ]);
      }
    }
    public function leaveRoom($room_id) {
      try {
        Artisan::call("chatmini:chatroom", [
          'op' => 'leave',
          '--user' => auth()->user()->id,
          '--id' => $room_id,
        ]);
        return \response([
          'error' => false,
          'chatroom' => "Left"
        ]);
      } catch (\Exception $exception) {
        return \response([
          'error' => true,
          'message' => "Internal server error. ".$exception->getMessage(),
        ]);
      }
    }

    public function getChatrooms($type) {
      if(in_array($type, $this->valid_chatroom_type)) {
        switch ($type) {
          case 'official':
            $official_rooms = Chatroom::where('user_id','=','1')->withCount('members')->orderBy('priority','DESC')->limit(5)->inRandomOrder()->get();
            $chatrooms = $official_rooms;
            break;
          case 'own':
            $own = Chatroom::where('user_id','=',auth()->user()->id)->withCount('members')->orderBy('priority','DESC')->orderBy('id','DESC')->limit(50)->get();
            $chatrooms = $own;
            break;
          case 'trending':
            $trending = Chatroom::where('user_id','!=','1')->has('members')->withCount('members')->orderBy('members_count','DESC')->limit(5)->inRandomOrder()->get();
            $chatrooms = $trending;
            break;
          case 'recent':
            $ids = DB::table('chatroom_recent_visited')->where('user_id','=', auth()->user()->id)->get()->reverse();
            $chatrooms = collect([]);
            foreach ($ids as $id) {
              if(count($chatrooms) == 5) {
                break;
              }
              $chatroom = Chatroom::where('id','=', $id->chatroom_id)->withCount('members')->first();
              $chatrooms->push($chatroom);
            }
            break;
          case 'gaming':
            $gammingids = [57,58,1133,980];
            $gaming_rooms = Chatroom::withCount('members')->has('game')->whereIn('user_id',$gammingids)->inRandomOrder()->limit(5)->get();
            $chatrooms = $gaming_rooms;
            break;
          case 'favourites':
            $fav_chatrooms = DB::table('chatroom_favourites')->where('user_id','=', auth()->user()->id)->get();
            $chatrooms = $fav_chatrooms->pluck('chatroom_id')->toArray();
            return Chatroom::withCount('members')->whereIn('id', $chatrooms)->get();
            break;
          default:
            $chatrooms = [];
        }
        return $chatrooms;
      } else {
        return \response(["Error"], 500);
      }
    }

    public function addAsFavourite(Request $request) {
      if($request->has('chatroom_id')) {
        $chatroom_id = $request->chatroom_id;
        $chatroom = Chatroom::where('id','=', $chatroom_id)->with(['favouritesOf'])->first();
        if($chatroom && !$chatroom->favouritesOf->contains(auth()->user()->id)) {
          $chatroom->favouritesOf()->attach(auth()->user()->id);
        }
        return [
          "error" => false,
          "message" => "Added as favourite chatroom."
        ];
      }
    }
    public function removeFromFavourite(Request $request) {
      if($request->has('chatroom_id')) {
        $chatroom_id = $request->chatroom_id;
        $chatroom = Chatroom::where('id','=', $chatroom_id)->with(['favouritesOf'])->first();
        if($chatroom && $chatroom->favouritesOf->contains(auth()->user()->id)) {
          $chatroom->favouritesOf()->detach(auth()->user()->id);
        }
        return [
          "error" => false,
          "message" => "Removed from favourite chatroom."
        ];
      }
    }

    public function create(Request $request) {
      $validator = Validator::make($request->all(), [
        'name' => 'required|min:5|max:15|unique:chatrooms|regex:/^[a-zA-Z][a-zA-Z0-9._ -]*$/',
        'description' => 'required|min:5|max:500',
      ]);
      if($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      if(auth()->user()->level->value < 10) {
        return [
          "error" => true,
          "message" => "You cannot create chatroom until you reach level 10."
        ];
      }
      Artisan::call("chatmini:chatroom", [
        'op' => 'create',
        '--user' => auth()->user()->id,
        '--name' => $request->name,
        '--d' => $request->description,
      ]);
      return [
        "error" => false,
        "message" => "Chatroom created successfully!"
      ];
    }

    public function sendMessage(Request $request) {
      $validator = Validator::make($request->all() , [
        'images.*' => 'mimes:jpeg,jpg,png,gif'
      ]);
      if($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      if($request->has('recording') && $request->has('chatroom_id')) {
        $user = auth()->user();
        $chatroom_id = $request->chatroom_id;
        $uuid = uniqid();
        $extension = $request->file('recording')->getClientOriginalExtension();
        $name = Carbon::now()->format('Ymd').'_'.$uuid.'.'.$extension;
        $record_path =url('/storage/uploads/files/'.$name);
        $request->file('recording')->storeAs('public/uploads/files/',$name);
        dispatch(new ChatroomMessage('send recording',$chatroom_id, $user->id, "TEST",["recording" => $record_path]));
        return \response([
          'error' => false,
          'message' => 'Message sent'
        ]);
      }
      if($request->has('type') && $request->has('message') && $request->has('chatroom_id')) {
        $chatroom_id = $request->chatroom_id;
        $user_id = auth()->user()->id;
        $message = $request->message;
        $images = [];
        if($request->hasFile('images')) {
          foreach($request->file('images') as $file) {
            $image = Image::make($file->getRealPath());
            $ext = $file->getClientOriginalExtension();
            $new_name = 'chatroom_images/'.time().uniqid().rand().'.'.$ext;
            $waterMarkUrl = storage_path('private/logo-png.png');
            $positions = [
              'top-left',
              'top',
              'top-right',
              'bottom-left',
              'bottom-right',
            ];
            $image->insert($waterMarkUrl, $positions[rand(0, count($positions) - 1)], 0, 0);
            $image->resize(650, 800, function ($constraints) {
              $constraints->aspectRatio();
            })->save(storage_path('app/public/'.$new_name));
            $url = asset(Storage::disk('public')->url($new_name));
            $images[] = [
              'path' => $url
            ];
          }
        }

        dispatch(new ChatroomMessage('send message', $chatroom_id, $user_id, $message, ["images" =>
          $images]));

        return \response([
          'error' => false,
          'message' => 'Message sent'
        ]);
      } else {
        \response([
          'error' => true,
          'message' => 'Invalid params..'
        ]);
      }
    }
    public function getInfo($chatroom_id, $type) {
      if(in_array($type, $this->valid_info_types)) {
        $chatroom = Chatroom::where('id','=',$chatroom_id)->first();
        if(!$chatroom) {
          return [
            "error" => false,
            "message" => "Chatroom not found."
          ];
        }
        $response = [
          'header' => '',
          'messages' => [],
        ];
        switch ($type) {
          case 'participants':
            $response['header'] = $chatroom->name." participants (".count($chatroom->members).")";
            $members = [];
            foreach ($chatroom->members()->orderBy('username')->get() as $member) {
              $sender_color = $member->color;
              # Send message to chatroom channel on message created.
              if($member->hasRole('User')) {
                $is_mod = $chatroom->moderators->contains($member->id);
                if($member->id == $chatroom->user_id) {
                  $sender_color = "#F7C600";
                }
                if($is_mod) {
                  $sender_color = "#F7C600";
                }
              }
              $mem = [
                "name" => $member->name,
                "id" => $member->id,
                "username" => $member->username,
                "level" => $member->level,
                "gender" => $member->gender,
                "country" => $member->country,
                "profile_picture" => $member->profile_picture,
                "color" => $sender_color,
              ];
              $members[] = $mem;
            }
            $response['messages'][] = [
              "title" => '',
              "title_color" => '#000',
              "description" => "-",
              "users" => $members
            ];
            return $response;
            break;
          case 'kick_list':
            $response['header'] = $chatroom->name." participants ";
            foreach ($chatroom->members as $member) {
              $response['messages'][] = [
                "title" => $member->username,
                "title_color" => $member->color,
                "description" => ""
              ];
            }
            return $response;
            break;
          case 'room_info':
            $response['header'] = $chatroom->name." info";
            $response['messages'][] = [
              "title" => "Room name",
              "title_color" => "#000",
              "description" => $chatroom->name
            ];
            $response['messages'][] = [
              "title" => "Owner",
              "title_color" => "#000",
              "description" => $chatroom->user->username
            ];
            $response['messages'][] = [
              "title" => "Description",
              "title_color" => "#000",
              "description" => $chatroom->description
            ];
            $response['messages'][] = [
              "title" => "Capacity",
              "title_color" => "#000",
              "description" => $chatroom->capacity
            ];
            $moderators = [];
            foreach ($chatroom->moderators as $moderator) {
              $moderators[] = $moderator->username;
            }
            $response['messages'][] = [
              "title" => "Moderators",
              "title_color" => "#000",
              "description" => implode(", ", $moderators)
            ];
            return $response;
            break;
          case 'balance':
            $user = auth()->user();
            $response['header'] = $user->username." balance";
            $response['messages'][] = [
              "title" => "",
              "title_color" => "#000",
              "description" => "Your main balance is credits ".$user->credit.". Thank you."
            ];
            return $response;
            break;
        }
      } else {
        return [
          'error' => true,
          'message' => 'Invalid info type.'
        ];
      }
    }

  public function searchChatroom(Request $request) {
    if($request->has('search')) {
      $search = trim($request->search);
      if(empty($search)) {
        return Chatroom::where('privacy','=','public')->withCount(['members'])->paginate(25);
      } else {
        return Chatroom::where('privacy','=','public')->withCount(['members'])->where('name','LIKE','%'.$search.'%')->paginate(25);
      }
    }
  }
}
