<?php

namespace App\Http\Controllers;

use App\Services\EmailService;

class FetchEmailsController
{
    private EmailService $email;

    /**
     * FetchEmailsController constructor.
     * @param EmailService $email
     */
    public function __construct(EmailService $email)
    {
        $this->email = $email;
    }

    public function __invoke()
    {
        $result = $this->email->all();

        return response()->json($result->toArray(), $result->error_code);
    }
}
