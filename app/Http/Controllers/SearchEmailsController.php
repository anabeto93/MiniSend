<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use App\DTOs\SearchEmailDto;
use Illuminate\Http\Request;

class SearchEmailsController
{
    private EmailService $email;

    /**
     * SearchEmailsController constructor.
     * @param EmailService $email
     */
    public function __construct(EmailService $email)
    {
        $this->email = $email;
    }

    public function __invoke(Request $request)
    {
        //this is a simple request, no need for a formRequest
        $request->validate([
            'sender' => ['bail', 'required_without_all:recipient,subject', 'min:1',],
            'recipient' => ['bail', 'required_without_all:sender,subject', 'min:1',],
            'subject' => ['bail', 'required_without_all:sender,recipient', 'min:1',],
        ]);

        $search = SearchEmailDto::fromRequest($request);

        $result = $this->email->search($search);

        return response()->json($result->toArray(), $result->error_code);
    }
}
