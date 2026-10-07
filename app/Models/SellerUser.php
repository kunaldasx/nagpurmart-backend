<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(array $array)
 */
class SellerUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seller_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'seller_id',
        'login_approval_status',
        'login_approval_requested_at',
        'login_approved_at',
        'login_approved_until',
    ];

    protected function casts(): array
    {
        return [
            'login_approval_requested_at' => 'datetime',
            'login_approved_at' => 'datetime',
            'login_approved_until' => 'datetime',
        ];
    }

    /**
     * Get the user associated with the seller-user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the seller associated with the seller-user relationship.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
