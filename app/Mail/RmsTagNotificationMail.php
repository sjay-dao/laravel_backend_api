<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RmsTagNotificationMail extends Mailable
{
    public array $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

   public function build()
    {
        return $this->subject('RMS Tag Notification')
            ->view('emails.rms-tag-notification');
    }
}