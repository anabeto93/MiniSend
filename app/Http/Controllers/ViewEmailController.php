<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;

class ViewEmailController
{
    private EmailService $email;

    /**
     * ViewEmailController constructor.
     * @param EmailService $email
     */
    public function __construct(EmailService $email)
    {
        $this->email = $email;
    }

    public function __invoke(string $email, Request $request)
    {
        $result = $this->email->find($email);

        if ($request->expectsJson()) {
            return response()->json($result->toArray(), $result->error_code);
        }

        if ($result->error_code != 200) {
            abort(404);
        }

        $email = $result->data['email'];

        return view('emails.show', compact('email'));
    }
}
