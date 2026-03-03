<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotentRequest extends Model
{
    protected $fillable = [
        'idempotency_key',
        'route',
        'response_status',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
        ];
    }
}
