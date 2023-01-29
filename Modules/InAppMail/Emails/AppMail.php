<?php

namespace Modules\InAppMail\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AppMail extends Mailable
{
    use Queueable, SerializesModels;

  public $sender;
  public $sender_name;
  public $subject;
  public $body;

  public function __construct($sender, $sender_name, $subject, $body) {
    $this->sender = $sender;
    $this->subject = $subject;
    $this->body = $body;
    $this->sender_name = $sender_name;
  }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      return $this
        ->from($this->sender,$this->sender_name)
        ->subject($this->subject)
        ->markdown('inappmail::emails.appmail');
    }
}
