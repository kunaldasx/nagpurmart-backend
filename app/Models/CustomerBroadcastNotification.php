<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBroadcastNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'action_url',
        'deep_link',
        'target_categories',
        'expires_at',
        'priority',
        'is_active',
        'status',
        'sent_at',
        'sent_count',
        'recipient_count',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
            'target_categories' => 'array',
            'sent_count' => 'integer',
            'recipient_count' => 'integer',
            'created_by' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
