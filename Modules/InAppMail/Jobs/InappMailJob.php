<?php

namespace Modules\InAppMail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Modules\InAppMail\Emails\AppMail;

class InappMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $type;
    public $data;
    public function __construct($type, $data) {
        $this->type = $type;
        $this->data = $data;
    }

    public function handle(){
        switch ($this->type) {
          case 'sendMail':
            $sender = $this->data['sender'];
            $sender_name = $this->data['sender_name'];
            $receiver = $this->data['receiver'];
            $subject = $this->data['subject'];
            $body = $this->data['body'];

            $email = new AppMail($sender, $sender_name, $subject, $body);
            Mail::to($receiver)->send($email);
            break;
        }
    }
}
