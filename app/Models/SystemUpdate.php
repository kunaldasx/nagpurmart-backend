<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SystemUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'package_name',
        'checksum',
        'status',
        'applied_by',
        'applied_at',
        'notes',
        'log',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
