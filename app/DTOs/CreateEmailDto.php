<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class CreateEmailDto
{
    public string $sender;
    public array $recipients;
    public string $subject;
    public ?string $text_content;
    public ?string $html_content;
    public ?array $attachments;

    public function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        $keys = ['text_content', 'html_content', 'attachments'];

        foreach ($keys as $key) {
            if (!isset($this->$key) || !array_key_exists($key, $properties)) {
                $this->$key = null;
            }
        }
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'sender' => $request->input('sender') ?: $request->user()->email,
            'recipients' => $request->input('recipients'),
            'subject' => $request->input('subject'),
            'text_content' => $request->input('text_content'),
            'html_content' => $request->input('html_content'),
            'attachments' => $request->file('attachments'),
        ]);
    }
}
