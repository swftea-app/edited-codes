<?php

namespace Modules\Program\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Entities\Notification;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\Program\Entities\NewPromotions;
use Modules\Program\Entities\ProgramApplication;
use Modules\UserSystem\Entities\User;
use function GuzzleHttp\Promise\all;

class ProgramController extends Controller {
//  Merchant Panel
    public function requestForMerchantship(Request $request) {
      if($request->has('under_of') && $request->has('message')) {
        $response = [
          'header' => 'Error!!',
          'messages' => [],
        ];
        $under_of = $request->under_of;
        $message = $request->message;
        $requester = User::where('id','=',auth()->user()->id)->with(['roles'])->first();
        $requester_roles = $requester->roles->pluck('roles')->toArray();
        # validate mentor
        $mentor = User::where('username','=', $under_of)->with(['roles'])->first();
        if(!$mentor) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => "The requested user is not found."
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // Not valid user
        $mentor_roles = $mentor->roles->pluck('name')->toArray();
        if(!in_array("Mentor", $mentor_roles)) {
          $response['messages'][] = [
            "title" => $mentor_roles,
            "title_color" => "#000",
            "description" => 'This user is not valid mentor.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // not valid mentor
//        Already applien one
        $if_applied = ProgramApplication::where('user_id','=', auth()->user()->id)->where('status','>',0)->where('status','<', 3)->count();
        if($if_applied) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You have pending application. Please consult with your mentor for clearing the previous application.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // if applied previously
        if($requester->level->value < config('program.min_level_to_merchant')) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You haven\'t meet the level criteria for being merchant.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // level criteria
        $access = true;
        foreach ($requester_roles as $role) {
          if($role == 'Official') {
            $access = false;
            break;
          }
          if($role == 'Mentor Head') {
            $access = false;
            break;
          }
          if($role == 'Mentor') {
            $access = false;
            break;
          }
        }
        if(!$access) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You cannot use merchant panel.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        }
        # All good
        $merchantship = new ProgramApplication();
        $merchantship->title = "Request for merchant program.";
        $merchantship->user_id = $requester->id;
        $merchantship->under_of = $mentor->id;
        $merchantship->type = "merchantship";
        $merchantship->message = $message;
        $merchantship->save();
        $response['header'] = "Congratulations !!";
        $response['messages'][] = [
          "title" => "",
          "title_color" => "#000",
          "description" => 'You have successfully applied for merchantship program. Once your mentor accepts your proposal, we will proceed further. Notification will be sent to you with the updates of your application. Thank you for using Swftea. Have fun.'
        ];
        return [
          'error' => false,
          'message' => $response
        ];
      }
    }

    public function requestForMentorship(Request $request) {
      if($request->has('under_of') && $request->has('message')) {
        $response = [
          'header' => 'Error!!',
          'messages' => [],
        ];
        $under_of = $request->under_of;
        $message = $request->message;
        $requester = User::where('id','=',auth()->user()->id)->with(['roles'])->first();
        $requester_roles = $requester->roles->pluck('roles')->toArray();
        # validate mentor
        $mentor = User::where('username','=', $under_of)->with(['roles'])->first();
        if(!$mentor) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => "The requested user is not found."
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // Not valid user
        $mentor_roles = $mentor->roles->pluck('name')->toArray();
        if(!in_array("Mentor Head", $mentor_roles)) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'This user is not valid mentor head.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // not valid mentor
        if(in_array("Mentor Head", $requester_roles)) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'Your renewal is locked. Please contact staff for renewing your program.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // If sender is requester
        $if_applied = ProgramApplication::where('user_id','=', auth()->user()->id)->where('status','>',0)->where('status','<', 3)->count();
        if($if_applied) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You have pending application. Please consult with your mentor for clearing the previous application.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // if applied previously
        if($requester->level->value < config('program.min_level_to_mentor')) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You haven\'t meet the level criteria for being mentor.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        } // level criteria
        $access = true;
        foreach ($requester_roles as $role) {
          if($role == 'Official') {
            $access = false;
            break;
          }
          if($role == 'Mentor Head') {
            $access = false;
            break;
          }
          if($role == 'User') {
            $access = false;
            break;
          }
        }
        if(!$access) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You cannot use mentor panel.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        }
        if($requester->id == $mentor->id) {
          $response['messages'][] = [
            "title" => "",
            "title_color" => "#000",
            "description" => 'You cannot use request for own id.'
          ];
          return [
            'error' => true,
            'message' => $response
          ];
        }
        # All good
        $merchantship = new ProgramApplication();
        $merchantship->title = "Request for mentor program.";
        $merchantship->user_id = $requester->id;
        $merchantship->under_of = $mentor->id;
        $merchantship->type = "mentorship";
        $merchantship->message = $message;
        $merchantship->save();
        $response['header'] = "Congratulations !!";
        $response['messages'][] = [
          "title" => "",
          "title_color" => "#000",
          "description" => 'You have successfully applied for mentorship program. Once your head mentor accepts your proposal, we will proceed further. Notification will be sent to you with the updates of your application. Thank you for using Swftea. Have fun.'
        ];
        return [
          'error' => false,
          'message' => $response
        ];
      }
    }

    public function merchantpanel() {
      $user = User::where("id","=", auth()->user()->id)->with(['roles','taggedBy','tags'])->first();
      $user->makeVisible(['credit']);
      $roles = $user->roles->pluck('name')->toArray();
      $is_merchant = false;
      $access = false;
      if(in_array("Merchant", $roles)) {
        $is_merchant = true;
        $access = true;
      }
      if(in_array("User", $roles)) {
        $access = true;
      }
      $user->isMerchant = $is_merchant;
      $user->access = $access;
      $user->merchantpanel = [
        'merchant_cost' => config('program.amount_to_be_merchant'),
        'merchant_min_level' => config('program.min_level_to_merchant'),
      ];
      $user->mentorpanel = [
        'mentor_cost' => config('program.amount_to_be_mentor'),
        'mentor_min_level' => config('program.min_level_to_mentor'),
      ];
      $user->merchant_expiry = $user->program_expiry == null ? Carbon::now()->addSeconds(rand(0, 3000))->diffForHumans() : Carbon::parse($user->program_expiry)->diffForHumans();
      $user->mentor_expiry = $user->program_expiry == null ? Carbon::now()->addSeconds(rand(0, 3000))->diffForHumans(): Carbon::parse($user->program_expiry)->diffForHumans();
      $user->barCompleted = getMerchantMentorBarPercent("Merchant", $user->program_point);
      return $user;
    }

    public function mentorpanel() {
      $user = User::where("id","=", auth()->user()->id)->with(['roles','taggedBy','tags'])->first();
      $user->makeVisible(['credit']);
      $roles = $user->roles->pluck('name')->toArray();
      $is_mentor = false;
      $access = false;
      if(in_array("Mentor", $roles)) {
        $is_mentor = true;
        $access = true;
      }
      if(in_array("Mentor Head", $roles)) {
        $is_mentor = true;
        $access = true;
      }
      if(in_array("Official", $roles)) {
        $is_mentor = true;
        $access = true;
      }
      $user->isMentor = $is_mentor;
      $user->access = $access;
      $user->mentorpanel = [
        'mentor_cost' => config('program.amount_to_be_mentor'),
        'mentor_min_level' => config('program.min_level_to_mentor'),
      ];
      $user->mentor_expiry = $user->mentor_expiry == null ? Carbon::now()->addSeconds(rand(0, 3000))->diffForHumans(): Carbon::parse($user->mentor_expiry)->diffForHumans();
      $user->barCompleted = getMerchantMentorBarPercent("Mentor", $user->program_point);
      return $user;
    }

    public function myAppliedList(Request $request) {
      if($request->has('type')) {
        $type = $request->type;
        $user = auth()->user();
        if($type == 'all') {
          return ProgramApplication::where('user_id','=', $user->id)->with(['head_person'])->where('status','!=',4)->orderBy('id','DESC')->limit(40)->get();
        } else if($type == 'active') {
          return ProgramApplication::where('user_id','=', $user->id)->where('status','>',0)->where('status','<',3)->with(['head_person'])->orderBy('id','DESC')->limit(40)->get();
        }
      }
    }

    public function myActionList(Request $request) {
      if($request->has('type')) {
        $type = $request->type;
        $user = auth()->user();
        if($type == 'all') {
          return ProgramApplication::where('under_of','=', $user->id)->with(['sender'])->where('status','!=',0)->where('status','!=',4)->orderBy('id','DESC')->orderBy('status')->limit(40)->get();
        } else if($type == 'active') {
          return ProgramApplication::where('under_of','=', $user->id)->with(['sender'])->where('status','>',0)->where('status','<',3)->orderBy('id','DESC')->limit(40)->get();
        }
      }
    }

    public function takeAction(Request $request) {
      if($request->has('application_id') && $request->has('type')) {
        $response = [
          'header' => 'Error!!',
          'messages' => [],
        ];
        $application_id = $request->application_id;
        $type = $request->type;
        $application = ProgramApplication::where('id','=', $application_id)->where('under_of','=',auth()->user()->id)->where('status','=',1)->first();
        if($application) {
          if($application->under_of == auth()->user()->id) {
            $accepter = User::find(auth()->user()->id);
            $accepted = User::find($application->user_id);
            if($type == 'accept') {
              # Check if have balance
              if($application->type == 'mentorship') {
                $total_cost = config('program.amount_to_be_mentor');
                if($accepter->credit < $total_cost) {
                  $response['messages'][] = [
                    "title" => "",
                    "title_color" => "#000",
                    "description" => 'You dont have enough balance to accept this user as mentor. Minimum balance needed: '.$total_cost.' credits'
                  ];
                  return [
                    "error" => true,
                    "message" => $response
                  ];
                } // Have balance
                # All good?

                $accepter->histories()->create([
                  'type' => 'Transfer',
                  'creditor' => 'user',
                  'creditor_id' => 1,
                  'message' => 'Added '.$accepted->username.' on mentorship program.',
                  'old_value' => $accepter->credit,
                  'new_value' => $accepter->credit - $total_cost,
                  'user_id' => $accepter->id
                ]); // Added history

                DB::table('users')
                  ->where('id','=',$accepter->id)
                  ->decrement('credit', $total_cost);

                $application->status_message = 'Verified. Admin verification under process.';
                $application->status = 2;
                $application->save();

                $response['header'] = "Congratulations !!";
                $response['messages'][] = [
                  "title" => "",
                  "title_color" => "#000",
                  "description" => 'Your request was successful. Thank you.'
                ];
                $verifiedApplication = new NewPromotions();
                $verifiedApplication->user_id = $application->user_id;
                $verifiedApplication->type = $application->type;
                $verifiedApplication->under_of = $application->under_of;
                $verifiedApplication->application_id = $application->id;
                $verifiedApplication->save();
                return [
                  "error" => false,
                  "message" => $response
                ];
              }
              if($application->type == 'merchantship') {
                $total_cost = config('program.amount_to_be_merchant');
                if($accepter->credit < $total_cost) {
                  $response['messages'][] = [
                    "title" => "",
                    "title_color" => "#000",
                    "description" => 'You dont have enough balance to accept this user as merchant. Minimum balance needed: '.$total_cost.' credits'
                  ];
                  return [
                    "error" => true,
                    "message" => $response
                  ];
                } // Have balance
                # All good?

                $accepter->histories()->create([
                  'type' => 'Transfer',
                  'creditor' => 'user',
                  'creditor_id' => 1,
                  'message' => 'Added '.$accepted->username.' on merchantship program.',
                  'old_value' => $accepter->credit,
                  'new_value' => $accepter->credit - $total_cost,
                  'user_id' => $accepter->id
                ]); // Added history

                DB::table('users')
                  ->where('id','=',$accepter->id)
                  ->decrement('credit', $total_cost);

                $application->status_message = 'Verified. Admin verification under process.';
                $application->status = 2;
                $application->save();

                $response['header'] = "Congratulations !!";
                $response['messages'][] = [
                  "title" => "",
                  "title_color" => "#000",
                  "description" => 'Your request was successful. Thank you.'
                ];
                $verifiedApplication = new NewPromotions();
                $verifiedApplication->user_id = $application->user_id;
                $verifiedApplication->type = $application->type;
                $verifiedApplication->under_of = $application->under_of;
                $verifiedApplication->application_id = $application->id;
                $verifiedApplication->save();
                return [
                  "error" => false,
                  "message" => $response
                ];
              }
            }
            if($type =='reject') {
              $application->status_message = 'Your application is rejected.';
              $application->status = 0;
              $application->save();

              dispatch(new NotificationJob('promotion_rejected', $application));
              $response['header'] = "Congratulations !!";
              $response['messages'][] = [
                "title" => "",
                "title_color" => "#000",
                "description" => 'Your request was successful. Thank you.'
              ];
              return [
                "error" => false,
                "message" => $response
              ];
            }
          }
        }
      }
    }
}
