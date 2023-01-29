<?php

namespace Modules\UserSystem\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Modules\UserSystem\Emails\VerifyEmail;

class UserVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $type;
  public $data;
  public function __construct($type, $data) {
    $this->type = $type;
    $this->data = $data;
  }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      switch ($this->type) {
        case 'send verification mail':
          $sender = 'info@swftea.com';
          $sender_name = 'Swftea Support';
          $receiver = $this->data['receiver'];
          $subject = 'Email Verification';
          $token = $this->data['token'];
          $username = $this->data['username'];
          $body = 'Your verification token is '.$token.' for username '.$username;

          $email = new VerifyEmail($sender, $sender_name, $subject, $body);
          Mail::to($receiver)->send($email);
          break;
      }
    }
}
