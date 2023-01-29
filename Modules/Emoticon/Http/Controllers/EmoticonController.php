<?php

namespace Modules\Emoticon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Emoticon\Entities\EmotionCategory;
use Modules\UserSystem\Entities\User;

class EmoticonController extends Controller {
   public function categories(Request $request) {
     if($request->has('discount')) {
       $discount = $request->discount;
       if($discount) {
         return EmotionCategory::withCount(['users','emoticons'])->where('discount','>',0)->orderBy('order')->orderByDesc('id', 'DESC')->paginate(25);
       } else {
         return EmotionCategory::withCount(['users','emoticons'])->orderBy('order')->orderByDesc('id')->paginate(25);
       }
     }
   }
   public function buy(Request $request) {
     if($request->has('category_id')) {
       $category_id= $request->category_id;
       $category = EmotionCategory::where("id","=", $category_id)->first();
       if(!$category) {
         $response = [];
         $response['header'] = "Error!!";
         $response['messages'][] = [
           "title" => "",
           "title_color" => "#000",
           "description" => "Category not found"
         ];
         return [
           "error" => true,
           "message" => $response
         ];
       }
       if($category->purchased) {
         $response = [];
         $response['header'] = "Error!!";
         $response['messages'][] = [
           "title" => "",
           "title_color" => "#000",
           "description" => "You have already purchased this emoticons pack."
         ];
         return [
           "error" => true,
           "message" => $response
         ];
       }
       #calculate actual price
       $actual_price = $category->price - (($category->discount / 100) * $category->price);
       #check if user has amount
       if(auth()->user()->credit < $actual_price) {
         $response = [];
         $response['header'] = "Error!!";
         $response['messages'][] = [
           "title" => "",
           "title_color" => "#000",
           "description" => 'Insufficient balance. Please recharge your account.'
         ];
         return [
           "error" => true,
           "message" => $response
         ];
       }
//       Everything Good
       $category->users()->attach(auth()->user()); // Purchased
       $user = User::where('id','=', auth()->user()->id)->first();
       $user->histories()->create([
         'type' => 'Emoticons',
         'creditor' => 'user',
         'creditor_id' => 1,
         'message' => 'Purchased '.$category->title.' emoticons pack.',
         'old_value' => $user->credit,
         'new_value' => $user->credit - $actual_price,
         'user_id' => $user->id
       ]); // Added history
       DB::table('users')
         ->where('id','=',$user->id)
         ->decrement('credit', $actual_price);
       Cache::forget('emoji_'.auth()->user()->id); // Cache forget
       $response = [];
       $response['header'] = "Congratulations!!";
       $response['messages'][] = [
         "title" => "",
         "title_color" => "#000",
         "description" => 'You have successfully purchased ('.$category->title.')'
       ];
       return [
         'error' => false,
         'message' => $response
       ];
     }
   }
   public function myCategories() {
     $user = User::find(auth()->user()->id);
     return EmotionCategory::findByUser($user, 25);
  }
}
