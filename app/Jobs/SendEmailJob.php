<?php

namespace App\Jobs;

use App\Mail\SendTextEmail;
use App\Models\Email;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $email_id;

    /**
     * SendEmailJob constructor.
     * @param string $email_id
     */
    public function __construct(string $email_id)
    {
        $this->email_id = $email_id;
        $this->afterCommit = true;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = Email::where('uuid', $this->email_id)->first();

        try {
            if ($email->text_content) {
                Mail::to($email->to)->send(new SendTextEmail($email));
            } else {

            }

            $email->update([
                'status' => 'SENT',
            ]);
        } catch (\Exception|\Throwable $e) {
            //regardless of the error, fail it
            $email->update([
                'status' => 'FAILED'
            ]);
        }
    }
}
