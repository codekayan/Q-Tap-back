<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class active_account extends Mailable
{
    use Queueable, SerializesModels;
    public $msg;

    /**
     * Create a new message instance.
     */
    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Active Account',
        );
    }

    public function build()
    {
        return $this->subject('Active Account')
            ->view('mail.active_account')
            ->with([
                'msg' => $this->msg
            ]);
    }
    public function attachments(): array
    {
        return [];
    }
}
