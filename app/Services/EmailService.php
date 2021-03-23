<?php

namespace App\Services;

use App\DTOs\CreateEmailDto;
use App\DTOs\SearchEmailDto;
use App\DTOs\ServiceResponse;
use App\Jobs\SendEmailJob;
use App\Models\Email;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailService
{
    public function create(CreateEmailDto $emailDto): ServiceResponse
    {
        DB::beginTransaction();

        try {
            $emails = [];

            $attachments = null;

            if ($emailDto->attachments) {
                $attachments = $this->uploadAttachments($emailDto->attachments);
            }

            $jobs = [];

            foreach ($emailDto->recipients as $i => $recipient) {
                $props = [
                    'uuid' => Str::orderedUuid()->toString(),
                    'from' => $emailDto->sender,
                    'to' => $recipient,
                    'subject' => $emailDto->subject,
                    'status' => 'POSTED',
                    'text_content' => $emailDto->text_content,
                    'html_content' => $emailDto->html_content,
                    'attachments' => $attachments ? json_encode($attachments) : null,
                ];

                $temp = Email::create($props);

                $emails[$i] = $temp;

                $jobs[] = new SendEmailJob($props['uuid']);
            }

            if (count($jobs) == 1) {
                dispatch($jobs[0])->delay(now()->addSeconds(2));
            } else {
                Bus::batch($jobs)->dispatch();
            }

            DB::commit();
        } catch (\Exception|\Throwable $e) {
            DB::rollBack();

            $error = [];

            if (app()->environment(['local', 'testing'])) {
                $error = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            return new ServiceResponse('Declined', "Failed sending email(s).", 500, $error);
        }

        if (count($emailDto->recipients) == 1) {
            $message = "Sending email.";
            $data = [
                'email' => $emails[0],
            ];
        } else {
            $message = "Sending emails.";
            $data = [
                'emails' => $emails,
            ];
        }

        return new ServiceResponse('Success', $message, 201, $data);
    }

    public function find(string $uuid): ServiceResponse
    {
        $email = Email::where('uuid', $uuid)->first();

        if (!$email) {
            return new ServiceResponse('Declined', 'Email not found.', 404);
        }

        return new ServiceResponse('Success', 'Email found.', 200, [
            'email' => $email,
        ]);
    }

    public function search(SearchEmailDto $search): ServiceResponse
    {
        $query = Email::query();

        if ($search->subject) {
            $query->where('subject', 'like', '%' . $search->subject . '%');
        }

        if ($search->sender) {
            $query->where('from', $search->sender);
        }

        if ($search->recipient) {
            $query->where('to', $search->recipient);
        }

        $all = $query->get(); //No need to paginate at this time

        if ($all->count() == 0) {
            return new ServiceResponse('Success', 'No matching emails found.', 404);
        }

        return new ServiceResponse('Success', 'Emails found.', 200, [
            'emails' => $all,
        ]);
    }

    private function uploadAttachments(array $files): array
    {
        $names = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $fEx = $file->getClientOriginalName();
                $fn = pathinfo($fEx, PATHINFO_FILENAME);
                $ex = $file->getClientOriginalExtension();
                $timestamp = implode("_", explode("-", str_replace(" ", "-", str_replace(":", "-", now()->toDateTimeString()))));
                $name = $timestamp . "_" . $fn . "." . $ex;

                $file->storeAs(storage_path('attachments'), $name);

                $names[] = $name;
            }
        }

        return array_values($names);
    }
}
