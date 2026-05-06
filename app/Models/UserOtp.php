<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserOtp extends Model
{
    protected $fillable = [
        'mobile',
        'otp',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Set the OTP attribute (encrypted)
     */
    // public function setOtpAttribute($value): void
    // {
    //     $this->attributes['otp'] = Crypt::encryptString($value);
    // }

    /**
     * Get the OTP attribute (decrypted)
     */
    // public function getOtpAttribute($value): string
    // {
    //     return Crypt::decryptString($value);
    // }

    /**
     * Scope to get active (non-expired, non-verified) OTPs
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNull('verified_at');
    }

    /**
     * Scope to get OTPs for a specific mobile number
     */
    public function scopeForMobile($query, string $mobile)
    {
        return $query->where('mobile', $mobile);
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Check if max attempts reached
     */
    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= 3;
    }

    /**
     * Increment attempt counter
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
