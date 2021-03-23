<?php

namespace App\DTOs;

class ServiceResponse
{
    public string $status;
    public string $message;
    public int $error_code;
    public array $data;

    /**
     * ServiceResponse constructor.
     * @param string $status
     * @param string $message
     * @param int $error_code
     * @param array|null $data
     */
    public function __construct(string $status, string $message, int $error_code, ?array $data = [])
    {
        $this->status = $status;
        $this->message = $message;
        $this->error_code = $error_code;
        $this->data = $data ?: [];
    }

    public function toArray(): array
    {
        $result = [
            'status' => $this->status,
            'message' => $this->message,
            'error_code' => $this->error_code,
        ];

        if (count($this->data) > 0) {
            $result['data'] = $this->data;
        }

        return $result;
    }
}
