<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function getAttachmentsAttribute($value)
    {
        if ($value) {
            $value = json_decode($value, true);

            if (is_string($value)) {
                //just once more
                $value = json_decode($value, true);
            }
        }

        return $value;
    }
}
