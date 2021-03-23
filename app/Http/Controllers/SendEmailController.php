<?php

namespace App\Http\Controllers;

use App\DTOs\CreateEmailDto;
use App\Http\Requests\CreateEmailFormRequest;
use App\Services\EmailService;

class SendEmailController
{
    private EmailService $email;

    public function __construct(EmailService $email)
    {
        $this->email = $email;
    }

    public function __invoke(CreateEmailFormRequest $request)
    {
        $createDto = CreateEmailDto::fromRequest($request);

        $result = $this->email->create($createDto);

        if ($request->expectsJson()) {
            return response()->json($result->toArray(), $result->error_code);
        }

        if ($result->error_code != 201) {
            return redirect()->back()->withErrors($result->data);
        }

        $e = $result->data['email'];

        return redirect()->to(route('emails.show', ['email' => $e->uuid]));
    }
}
