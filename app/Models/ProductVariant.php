<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $appends = ['image'];
    protected $fillable = [
        'uuid',
        'product_id',
        'title',
        'slug',
        'weight',
        'height',
        'breadth',
        'length',
        'availability',
        'provider',
        'provider_product_id',
        'provider_json',
        'barcode',
        'visibility',
        'is_default'
    ];

    protected $casts = [
        'provider_json' => 'array',
        'availability' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function getImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::VARIANT_IMAGE());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class, 'product_variant_id');
    }

    public function storeProductVariants(): HasMany
    {
        return $this->hasMany(StoreProductVariant::class, 'product_variant_id')->orderByDesc('stock');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_variant_id');
    }

    public function isInUserCart(): array
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return ['exists' => false, 'cart_item_id' => null]; // User isn't authenticated
        }

        $cartItem = $this->cartItems()
            ->whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        return [
            'exists' => !is_null($cartItem),
            'cart_item_id' => $cartItem?->id
        ];
    }

    /**
     * Get store product variant by store ID
     */
    public function storeProductVariantByStore(int $storeId): HasOne
    {
        return $this->hasOne(StoreProductVariant::class, 'product_variant_id')
            ->where('store_id', $storeId);
    }

    protected static function booted(): void
    {
        static::deleting(function ($variant) {
            CartItem::where('product_variant_id', $variant->id)->delete();
        });

    }
}
