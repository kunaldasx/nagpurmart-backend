<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(array $array)
 * @method static where(string $string, mixed $value)
 * @method static find(mixed $id)
 */
class UserFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_type',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'fcm_token' => 'string',
            'device_type' => 'string',
        ];
    }

    /**
     * Get the user that owns the FCM token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
