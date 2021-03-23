<?php

namespace App\Mail;

use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTextEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Email $email;

    /**
     * Create a new message instance.
     * @param Email $email
     * @return void
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->email;

        $mail = $this->view('emails.simple', compact('email'))
        ->from($email->from)->subject($email->subject);

        if (is_array($email->attachments) && count($email->attachments) > 0) {
            foreach ($email->attachments as $file) {
                $name = "attachments/" . $file;
                $mail->attachFromStorage($name, $file);
            }
        }

        return $mail;
    }
}
