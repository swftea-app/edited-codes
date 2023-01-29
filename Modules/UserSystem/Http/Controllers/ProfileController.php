<?php

namespace Modules\UserSystem\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Image;
use Modules\Level\Entities\Level;
use Modules\Level\Jobs\LevelJob;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\Program\Entities\MerchantTag;
use Modules\UserSystem\Entities\User;
use Stevebauman\Location\Facades\Location;

class ProfileController extends Controller
{
  public function passwordResetDone() {
    return view('usersystem::password_reset_done');
  }
  public function searchUser(Request $request)
  {
    if ($request->has('search')) {
      $search = trim($request->search);
      if (empty($search)) {
        return User::where('search_privacy', '=', false)->where('id', '!=', auth()->user()->id)->where('status', '!=', 0)->inRandomOrder()->paginate(25);
      } else {
        return User::where('search_privacy', '=', false)->where('id', '!=', auth()->user()->id)->where('status', '!=', 0)->where('username', 'LIKE', '%' . $search . '%')->paginate(25);
      }
    }
  }

  public function profile($user_id = null)
  {
    if ($user_id == null) {
      $user_id = auth()->user()->id;
    }
    if(is_numeric($user_id)) {
      $user = User::withCount(['sentgifts', 'footprints'])->with(['roles','badges', 'profile', 'emoticons.emoticons'])->where("id", "=", $user_id)->first();
    } else {
      $user = User::withCount(['sentgifts', 'footprints'])->with(['roles','badges', 'profile', 'emoticons.emoticons'])->where("username", "=", $user_id)->first();
    }
    if(!$user) {
      $user = User::withCount(['sentgifts', 'footprints'])->with(['roles','badges', 'profile', 'emoticons.emoticons'])->where("id", "=", auth()->user()->id)->first();
    }
    $user_id = $user->id;
    if ($user_id != auth()->user()->id) {
      $user->email = '';
      $user->credit = '';
      $user->tag_id = '';
      if (!$user->footprints->contains(auth()->user()->id)) {
        $user->footprints()->attach(auth()->user());
      }
      if (auth()->user()->hasLiked($user->profile)) {
        $user->likedByYou = true;
      } else {
        $user->likedByYou = false;
      }
    } else {
      $user->makeVisible(['email']);
    }
    //        check friend status
    if ($user->isFriendWith(auth()->user())) {
      $user->isFriendWithYou = true;
    } else {
      $user->isFriendWithYou = false;
    }
    if ($user->hasFriendRequestFrom(auth()->user())) {
      $user->hasFriendRequestFromYou = true;
    } else { // Friend request
      $user->hasFriendRequestFromYou = false;
    }
    if ($user->hasSentFriendRequestTo(auth()->user())) {
      $user->hasSentFriendRequestToYou = true;
    } else { // Friend request to you
      $user->hasSentFriendRequestToYou = false;
    }

//        For likes count
    $profile_likes = $user->profile->likes->count();
    $user->likes_count = $profile_likes;
    $user->friends_count = $user->getFriendsCount();
    $user->member_since = Carbon::parse($user->created_at)->diffForHumans();
    return $user;
  }

  public function updateStatus(Request $request)
  {
    if ($request->has('status')) {
      $user = User::where('id', '=', auth()->user()->id)->first();
      if ($user) {
        $status = trim($request->status);
        if (strlen($status) > 5 && strlen($status) < 300) {
          $user->main_status = $status;
          $user->save();
          return [
            "error" => false,
            "message" => [
              'title' => 'Success!!',
              'messages' => [
                [
                  "title" => "",
                  "title_color" => "#000",
                  "description" => "Your status is updated. Thank you."
                ]
              ]
            ]
          ];
        }
      }
    }
  }

  public function getAllFriends()
  {
    $user = auth()->user();
    // Get friends
    $friends = $user->getFriends($perPage = 4000000);
    foreach ($friends as $key => $friend) {
      $friends[$key]->friendship = $user->getFriendship($friend);
      $friends[$key]->color = $user->color;
    }
    return $friends;
  }

  public function sendFriendRequest(Request $request)
  {
    if ($request->has('to')) {
      $to = $request->to;
      $to_user = User::where('id', '=', $to)->first();
      $user = auth()->user();
      if ($to_user) {
        if (!$user->isFriendWith($to_user)) {
          if (!$user->hasSentFriendRequestTo($to_user)) {
            $user->befriend($to_user);
            dispatch(new NotificationJob("friend_request_sent", (object)[
              'from' => $user,
              'to' => $to_user
            ]));
            return [
              "error" => false,
              "message" => "Friend request sent!"
            ];
          }
        }
      }
    }
  }

  public function acceptFriendRequest(Request $request)
  {
    if ($request->has('to')) {
      $to = $request->to;
      $to_user = User::where('id', '=', $to)->first();
      $user = auth()->user();
      if ($to_user) {
        if ($to_user->hasSentFriendRequestTo($user)) {
          $user->acceptFriendRequest($to_user);
          return [
            "error" => false,
            "message" => "Friend request accepted!"
          ];
        }
      }
    }
  }

  public function cancelFriendRequest(Request $request)
  {
    if ($request->has('to')) {
      $to = $request->to;
      $to_user = User::where('id', '=', $to)->first();
      $user = auth()->user();
      if ($to_user) {
        if ($user->hasSentFriendRequestTo($to_user)) {
          $to_user->denyFriendRequest($user);
          return [
            "error" => false,
            "message" => "Friend request cancelled!"
          ];
        }
      }
    }
  }

  public function rejectFriendRequest(Request $request)
  {
    if ($request->has('to')) {
      $to = $request->to;
      $to_user = User::where('id', '=', $to)->first();
      $user = auth()->user();
      if ($to_user) {
        if (true) {
          $user->denyFriendRequest($to_user);
          return [
            "error" => false,
            "message" => "Friend request rejected!"
          ];
        }
      }
    }
  }

  public function unfriend(Request $request)
  {
    if ($request->has('to')) {
      $to = $request->to;
      $to_user = User::where('id', '=', $to)->first();
      $user = auth()->user();
      if ($to_user) {
        if ($user->isFriendWith($to_user)) {
          $user->unfriend($to_user);
          return [
            "error" => false,
            "message" => "Unfriend successful!"
          ];
        }
      }
    }
  }

  public function like(Request $request)
  {
    if ($request->has('to')) {
      $to = User::where('id', '=', $request->to)->first();
      if (!auth()->user()->hasLiked($to->profile)) {
        auth()->user()->like($to->profile);
        dispatch(new NotificationJob("like_notification", (object)[
          'from' => auth()->user(),
          'to' => $to
        ]));
        return [
          "error" => false,
          "message" => "Like successful"
        ];
      }
    }
  }

  public function unlike(Request $request) {
    if ($request->has('to')) {
      $to = User::where('id', '=', $request->to)->first();
      auth()->user()->unlike($to->profile);
      return [
        "error" => false,
        "message" => "Unlike successful"
      ];
    }
  }
  public function updatePicture(Request $request)
  {
    if ($request->file('profile_picture')) {
      $validator = Validator::make($request->all(), [
        'profile_picture' => 'mimes:jpeg,jpg,png,gif|required|max:10000'
      ]);
      if($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      if (true) {
        $file = Storage::disk('public')->putFile('pictures', $request->file('profile_picture'));
        $url = asset(Storage::disk('public')->url($file));
        $user = User::where('id', '=', auth()->user()->id)->first();
        $user->picture = $url;
        $user->save();
        return [
          "error" => false,
          "message" => $url
        ];
      } else {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }

    }
  }
  public function updateCoverPicture(Request $request) {
    if ($request->file('cover_picture')) {
      $validator = Validator::make($request->all(), [
        'cover_picture' => 'mimes:jpeg,jpg,png,gif|required|max:10000'
      ]);
      if($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      if (true) {
        $file = Storage::disk('public')->putFile('pictures', $request->file('cover_picture'));
        $url = asset(Storage::disk('public')->url($file));
        $user = User::where('id', '=', auth()->user()->id)->first();
        $user->cover_picture = $url;
        $user->save();
        return [
          "error" => false,
          "message" => $url
        ];
      } else {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }

    }
  }
  public function getCredit() {
    return [
      "error" => false,
      "credit" => auth()->user()->credit,
      "pincode" => auth()->user()->pin
    ];
  }
  public function getLocationInfo() {
    $ip = \request()->ip();
    return [
      'ip' => $ip,
      'location' => Location::get($ip)
    ];
  }
  public function getAccountInfo() {
    $spentToday = \auth()->user()->profile->today_spent_amount;
    $last_level_updated = \Carbon\Carbon::parse(\auth()->user()->level->created_at);
    $diff_from_now = \Carbon\Carbon::now()->diffInSeconds($last_level_updated);
    $level_up_second = getMaxLevelUpdateTime(\auth()->user()->level->value) * 60;


    $maxBar = getMaxLevelBarForLevel(\auth()->user()->level->value);
    $levelBar = \auth()->user()->profile->level_bar;
    if($diff_from_now > $level_up_second) {
      $diff_from_now = $level_up_second;
    }
    if($levelBar > $maxBar) {
      $levelBar = $maxBar;
    }


    if($diff_from_now > $level_up_second) {
      $update_time = "Time completed.";
      $levelBar += getLevelBarOfTime($level_up_second, \auth()->user()->level->value);
      $maxBar += getLevelBarOfTime($level_up_second, \auth()->user()->level->value);
    } else {
      $update_time = Carbon::parse(\auth()->user()->level->created_at)->addSeconds($level_up_second)->diffForHumans(['parts' => 2]);
      $levelBar += getLevelBarOfTime($diff_from_now, \auth()->user()->level->value);
      $maxBar += getLevelBarOfTime($level_up_second, \auth()->user()->level->value);
    }

    dispatch(new LevelJob('add bar', \auth()->user()->id, (object) [
      'amount' => 0.01
    ]));


    return [
      "error" => false,
      "credit" => auth()->user()->credit,
      "spentToday" => $spentToday,
      "level" => \auth()->user()->level->value,
      "nextUpdateTime" => $update_time,
      "levelbar" => $levelBar,
      "maxBar" =>  $maxBar,
      "currentLevelData" => getLevelInfo(\auth()->user()->level->value),
      "nextLevelData" => getLevelInfo(\auth()->user()->level->value + 1)
    ];
  }

  public function updateSystemPassword(Request $request) {
    $validator = Validator::make($request->all(), [
      'password' => 'required',
      'new_password' => [
        'required',
        'string',
        'confirmed',
        'min:6',
        'different:password',
        'regex:/[a-z]/',
        'regex:/[A-Z]/',
        'regex:/[0-9]/',
        'regex:/[@$!%*#?&]/'
      ]
    ]);

    if($validator->fails()) {
      return [
        "error" => true,
        "message" => $validator->errors()->first()
      ];
    }

    if (Hash::check($request->password, Auth::user()->password) == false)  {
      return [
        "error" => true,
        "message" => "Old password did not matched."
      ];
    }

    $user = Auth::user();
    $user->password = Hash::make($request->new_password);
    $user->save();

    return [
      'error' => false,
      'message' => 'Your password has been updated successfully.'
    ];
  }

  public function transfer(Request $request)
  {
    $validator = Validator::make($request->all(), [
      "username" => "required|confirmed",
      "pin" => "required|digits:6",
      "amount" => "required",
    ]);
    if($validator->fails()) {
      return [
        "error" => true,
        "message" => $validator->errors()->first()
      ];
    }
    if ($request->has('username') && $request->has('amount') && $request->has('pin')) {
      $to_user_username = $request->username;
      $amount = $request->amount;
      $pin = $request->pin;
      $to_user = User::where('username', '=', $to_user_username)->with(['roles'])->first();
      $from_user = User::where('id', '=', auth()->id())->with(['roles'])->first();
      if(!$to_user) {
        return [
        "error" => true,
          "message" => "Invalid suer"
        ];
      }
      $receivers_roles = $to_user->roles->pluck('name')->toArray();
      $sender_roles = $from_user->roles->pluck('name')->toArray();
      # validate to
      if (!$to_user->can('can receive credit')) {
        return [
          "error" => true,
          "message" => "Receiver cannot receive credit."
        ];
      }
      if (!$to_user) {
        return [
          "error" => true,
          "message" => "Invalid user"
        ];
      }
      if ($to_user->status == 0) {
        return [
          "error" => true,
          "message" => "This user is blocked from the system."
        ];
      }
      if ($to_user->username == $from_user->username) {
        return [
          "error" => true,
          "message" => "You cannot transfer to yourself."
        ];
      }
      #validate amount
      if ($amount > $from_user->credit) {
        return [
          "error" => true,
          "message" => "Not enough funds."
        ];
      }
      if ($amount < config('usersystem.minimum_transfer')) {
        return [
          "error" => true,
          "message" => "Minimum transferable amount is " . config('usersystem.minimum_transfer') . " credits."
        ];
      }
      if ($amount > config('usersystem.maximum_transfer')) {
        return [
          "error" => true,
          "message" => "Maximum transferable amount is " . config('usersystem.maximum_transfer') . " credits."
        ];
      }
      # validate from
      if (!$from_user->can('can send credit')) {
        return [
          "error" => true,
          "message" => "You cannot send credit."
        ];
      }
      if ($from_user->pin != $pin) {
        return [
          "error" => true,
          "message" => "Incorrect transaction pin. "
        ];
      }
      if ($from_user->credit < config('usersystem.minimum_credit_required_to_transfer')) {
        return [
          "error" => true,
          "message" => "You must have more than " . config('usersystem.minimum_credit_required_to_transfer') . " credits to start transfer."
        ];
      }
      if ($from_user->level->value < config('usersystem.minimum_level_required_to_transfer')) {
        return [
          "error" => true,
          "message" => "You are not eligible for transferring credit."
        ];
      }
      # validate credit
      $old_balance = $from_user->credit;
      $new_balance = $old_balance - $amount;
      if ($new_balance < config('usersystem.minimum_credit_required_to_transfer')) {
        return [
          "error" => true,
          "message" => "You must leave " . config('usersystem.minimum_credit_required_to_transfer') . " credits in your account."
        ];
      }
      #transfer restriction
      $can_transfer = false;
      if($from_user->can('send credit to anyone')) {
        $can_transfer = true;
      }
      if(!$can_transfer && in_array("Mentor", $sender_roles)) {
        if(in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Mentor", $receivers_roles) || in_array("Merchant", $receivers_roles) || in_array("Legends", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer && in_array("Merchant", $sender_roles)) {
        if(in_array("Merchant", $receivers_roles) || in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer && in_array("User", $sender_roles)) {
        if(in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer && in_array("Global Admin", $sender_roles)) {
        if(in_array("Merchant", $receivers_roles) || in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles) || in_array("Global Admin", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer && in_array("Senior Profile", $sender_roles)) {
        if(in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer && in_array("Legends", $sender_roles)) {
        if(in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles)) {
          $can_transfer = true;
        }
      }
      if(!$can_transfer) {
        return [
          "error" => true,
          "message" => "You cannot transfer credit to ".$to_user->username
        ];
      }
      # transfer restrictions
      $can_transfer = true;
      if(in_array("User", $sender_roles) || in_array("Senior Profile", $sender_roles) || in_array("Legends", $sender_roles)) {
        $can_transfer = false;
        $today_transferred_amount = $from_user->profile->today_transferred_amount + $amount;
        if($today_transferred_amount <= 100000) {
          $can_transfer = true;
        }
        if(!$can_transfer) {
          return [
            "error" => true,
            "message" => "Your transfer limit has exceed."
          ];
        }
      }
      # all good
      #check can tag
      $can_tag = false;
      if (in_array("Mentor", $sender_roles) || in_array("Mentor Head", $sender_roles) || in_array("Merchant", $sender_roles)) {
        if (in_array("Merchant", $sender_roles)) {
          if (in_array("User", $receivers_roles) || in_array("Senior Profile", $receivers_roles) || in_array("Legends", $receivers_roles)) {
            $can_tag = true;
          }
        }
        if (in_array("Mentor", $sender_roles)) {
          if (in_array("Merchant", $receivers_roles)) {
            $can_tag = true;
          }
        }
        if (in_array("Mentor Head", $sender_roles)) {
          if (in_array("Mentor", $receivers_roles)) {
            $can_tag = true;
          }
        }
      }
//        Tag system
      $total_transferred_amount = $from_user->profile->today_transferred_amount + $amount;
      DB::table('profiles')->where('user_id','=',$from_user->id)->update([
        'today_transferred_amount' => $total_transferred_amount
      ]);
      if ($to_user->tag_id == 1 && $can_tag && $amount >= config('usersystem.min_tag_credit', 8400)) { // untagged
        $to_user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Received credit transferred from " . $from_user->username,
          'old_value' => $to_user->credit,
          'new_value' => $to_user->credit + $amount,
          'user_id' => $to_user->id
        ]); // account history to receiver

        DB::table('users')
          ->where('id','=',$to_user->id)
          ->increment('credit', $amount);
        $to_user->tag_id = $from_user->id;
        $to_user->save();

        $from_user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Transferred credit to " . $to_user->username,
          'old_value' => $from_user->credit,
          'new_value' => $from_user->credit - $amount,
          'user_id' => $from_user->id
        ]); // account history to receiver
        DB::table('users')
          ->where('id','=',$from_user->id)
          ->decrement('credit', $amount);

        $tagged_till = Carbon::now()->addDays(30);
        $tag= new MerchantTag();
        $tag->user_of = $from_user->id;
        $tag->user_id = $to_user->id;
        $tag->expire_at = $tagged_till;
        $tag->save();
        return [
          'error' => false,
          'message' => 'Successful, ' . $amount . ' credits is transferred to ' . $to_user_username . " and tagged successfully till " . $tagged_till->diffForHumans()
        ];
      } else {
        $to_user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Received credit transferred from " . $from_user->username,
          'old_value' => $to_user->credit,
          'new_value' => $to_user->credit + $amount,
          'user_id' => $to_user->id
        ]); // account history to receiver
        DB::table('users')
          ->where('id','=',$to_user->id)
          ->increment('credit', $amount);

        $from_user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Transferred credit to " . $to_user->username,
          'old_value' => $from_user->credit,
          'new_value' => $from_user->credit - $amount,
          'user_id' => $from_user->id
        ]); // account history to receiver

        DB::table('users')
          ->where('id','=',$from_user->id)
          ->decrement('credit', $amount);

        return [
          'error' => false,
          'message' => 'Successful, ' . $amount . ' credits is transferred to ' . $to_user_username
        ];
      }
    }
  }

  public function updatePincode(Request $request)
  {
    if ($request->has('old_pin') && $request->has('new_pin')) {
      $validator = Validator::make($request->all(), [
        'new_pin' => 'required|numeric|confirmed|digits:6',
        'old_pin' => 'required|numeric|digits:6',
      ]);
      if ($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      $user = User::where('id', '=', auth()->user()->id)->first();
      if ($user) {
        $pin = $user->pin;
        if ($pin != $request->old_pin) {
          return [
            "error" => true,
            "message" => "Invalid old pin."
          ];
        }
        # All good?
        $user->pin = $request->new_pin;
        $user->save();
        return [
          "error" => false,
          "message" => "Pin successfully changed."
        ];
      }
    }
  }
  public function updateSettings(Request $request) {
    $valid_settings = ['search_privacy'];
    if ($request->has('type') && $request->has('value')) {
      $validator = Validator::make($request->all(), [
        'type' => 'required',
        'value' => 'required',
      ]);
      if ($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      if(!in_array($request->type, $valid_settings)) {
        return [
          "error" => true,
          "message" => "Fuck!!"
        ];
      }
      switch ($request->type) {
        case 'search_privacy':
          $val = $request->value;
          $val = boolval($val) ? 1 : 0;
          DB::table('users')->where('id','=', \auth()->user()->id)->update([
            'search_privacy' => $val
          ]);
          return [
            "error" => false,
            "message" => "Settings updated!"
          ];
          break;
      }
    }
  }

  function filterUserByRole($role) {
    $valid_roles = [
      'seniorprofiles' => 'Senior Profile',
      'legends' => 'Legends',
      'merchant' => 'Merchant',
      'mentor' => 'Mentor',
      'mhead' => 'Mentor Head',
      'gadmin' => 'Global Admin',
      'staff' => 'Official',
    ];
    if(array_key_exists($role, $valid_roles)) {
      $r = $valid_roles[$role];
      if($role == 'seniorprofiles') {
        $users = [];

        return $users;
      } else {
        return User::whereHas('roles', function ($q) use ($r) {
          $q->where("name", $r);
        })->get();
      }
    }
    return [
      'Fuck you!!'
    ];
  }

  public function verifyEmail(Request $request) {
    $validator = Validator::make($request->all(), [
      'token' => ['required','min:3'],
      'username' => ['required','min:3'],
      'email' => ['required','min:3'],
    ]);
    if($validator->fails()) {
      return [
        "error" => true,
        "message" => $validator->errors()->first()
      ];
    }
    $user = User::where("username","=",$request->username)->with('profile')->first();
    if(!$user) {
      return [
        "error" => true,
        "message" => "Invalid user"
      ];
    }
    if($user->email != $request->email) {
      return [
        "error" => true,
        "message" => "Invalid user email"
      ];
    }
    if($user->email_verified_at != NULL) {
      return [
        "error" => true,
        "message" => "Email already verified."
      ];
    }
    if($user->profile->verification_token != $request->token) {
      return [
        "error" => true,
        "message" => "Token not matched."
      ];
    }

    # all good
    $user->email_verified_at = new Carbon();
    $user->save();
    return [
      "error" => false,
      "message" => "Welcome ".$user->username." Please login to rock with swftea."
    ];
  }

  public function getFriendRequests() {
    $requests = collect([]);
    $users = auth()->user()->getFriendRequests();
    foreach ($users as $user) {
      $sender = $user->sender_type::find($user->sender_id);
      $requests->push($sender);
    }
    return $requests;
  }

}
