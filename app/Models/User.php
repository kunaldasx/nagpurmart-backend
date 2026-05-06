<?php

namespace App\Models;

use App\Enums\GuardNameEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Notifications\AdminPasswordResetNotification;
use App\Notifications\SellerPasswordResetNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method static create(array $array)
 * @method static where(string $string, mixed $email)
 * @method static find(mixed $user_id)
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, SoftDeletes, InteractsWithMedia;

    protected $appends = ['profile_image'];

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'referral_code',
        'friends_code',
        'reward_points',
        'remember_token',
        'status',
        'password',
        'access_panel',
        'iso_2',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'mobile' => 'string',
            'referral_code' => 'string',
            'friends_code' => 'string',
            'reward_points' => 'integer',
            'remember_token' => 'string',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'status' => 'boolean',
            'access_panel' => GuardNameEnum::class,
            'iso_2' => 'string',
            'country' => 'string',
        ];
    }

    /**
     * Override the default guard name from HasRoles trait.
     *
     * @return string
     */
    public function getDefaultGuardName(): string
    {
        return GuardNameEnum::fromString($this->access_panel->value)->value;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Request-level cache for seller data.
     */
    protected $cachedSeller = null;
    protected $sellerCacheChecked = false;

    public function seller()
    {
        // Return cached seller if already retrieved in this request
        if ($this->sellerCacheChecked) {
            return $this->cachedSeller;
        }

        // Check if the user is a seller (owner)
        $seller = Seller::where('user_id', $this->id)->first();
        if ($seller) {
            $this->cachedSeller = $seller;
            $this->sellerCacheChecked = true;
            return $seller;
        }
        // If not a seller, check the pivot table for a connection
        $sellerUser = SellerUser::where('user_id', $this->id)->first();
        if ($sellerUser) {
            $this->cachedSeller = Seller::find($sellerUser->seller_id);
            $this->sellerCacheChecked = true;
            return $this->cachedSeller;
        }

        // Return null if no seller is found
        $this->cachedSeller = null;
        $this->sellerCacheChecked = true;
        return null;
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id');
    }

    public function deliveryBoy(): HasOne
    {
        return $this->hasOne(DeliveryBoy::class, 'user_id', 'id');
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(UserFcmToken::class, 'user_id', 'id');
    }
    public function routeNotificationForFirebase()
    {
        // Return an array of FCM tokens for this user
        return $this->fcmTokens()->pluck('fcm_token')->filter()->toArray();
    }

    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'user_id', 'id');
    }

    public function getProfileImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::PROFILE_IMAGE());
    }

    public function OrderPaymentTransactions(): HasMany
    {
        return $this->hasMany(OrderPaymentTransaction::class, 'user_id', 'id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE())->singleFile();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        // Check if the user has admin access panel
        if ($this->access_panel && $this->access_panel->value === 'admin') {
            $this->notify(new AdminPasswordResetNotification($token));
        } elseif ($this->access_panel && $this->access_panel->value === 'seller') {
            // Check if the user has seller access panel
            $this->notify(new SellerPasswordResetNotification($token));
        } else {
            // Use Laravel's default password reset notification for regular users
            $this->notify(new ResetPassword($token));
        }
    }

    protected static function booted(): void
    {
        static::deleting(function ($user) {
            $user->clearMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE());
        });
    }
}
