<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'recipient',
        'subject',
        'template',
        'channel',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at'  => 'datetime',
        ];
    }
}
