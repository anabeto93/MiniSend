<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class SearchEmailDto
{
    public ?string $sender;
    public ?string $recipient;
    public ?string $subject;

    public function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }

        $keys = ['sender', 'recipient', 'subject'];

        foreach ($keys as $key) {
            if (!isset($this->$key) || !array_key_exists($key, $properties)) {
                $this->$key = null;
            }
        }
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'sender' => $request->input('sender'),
            'recipient' => $request->input('recipient'),
            'subject' => $request->input('subject'),
        ]);
    }
}
