<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTPMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $verificationLink;


    public function __construct($otp, $verificationLink)
    {
        $this->otp = $otp;
        $this->verificationLink = $verificationLink;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Qtap family',
        );
    }


    // public function content(): Content
    // {
    //     return new Content(lainavacompany@gmail.com
    //         view: 'mail.OTP',
    //     );
    // }


    public function build()
    {
        return $this->subject('Your OTP Code')
            ->view('mail.OTP')
            ->with([
                'otp' => $this->otp,
                'verificationLink' => $this->verificationLink
            ]);
    }


    public function attachments(): array
    {
        return [];
    }
}
