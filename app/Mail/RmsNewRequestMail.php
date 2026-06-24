<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RmsNewRequestMail extends Mailable
{
    public $requests;

    public function __construct($requests)
    {
        $this->requests = $requests;
    }

    public function build()
    {
        return $this->subject('New RMS Requests')
            ->view('emails.rms-new-requests');
    }
}