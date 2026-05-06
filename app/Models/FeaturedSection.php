<?php

namespace App\Models;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\FeaturedSection\FeaturedSectionTypeEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static create(mixed $validated)
 */
class FeaturedSection extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = [
        'background_image',
        'desktop_4k_background_image',
        'desktop_fdh_background_image',
        'tablet_background_image',
        'mobile_background_image',
    ];
    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'style',
        'section_type',
        'sort_order',
        'status',
        'scope_type',
        'scope_id',
        'background_type',
        'background_color',
        'text_color'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'scope_id' => 'integer',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    /**
     * Get the categories associated with the featured section.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_featured_section')
            ->withTimestamps();
    }

    /**
     * Get the scope category for this featured section.
     */
    public function scopeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function getBackgroundImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::FEATURED_SECTION_BACKGROUND_IMAGE());
    }

    public function getDesktop4kBackgroundImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_4K());
    }

    public function getDesktopFdhBackgroundImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::FEATURED_SECTION_BG_DESKTOP_FHD());
    }

    public function getTabletBackgroundImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::FEATURED_SECTION_BG_TABLET());
    }

    public function getMobileBackgroundImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::FEATURED_SECTION_BG_MOBILE());
    }

    /**
     * Get all child category IDs recursively for a given category ID.
     */
    private function getAllChildCategoryIds($categoryId): array
    {
        $childIds = [$categoryId]; // Include the parent category itself

        $category = Category::find($categoryId);
        if ($category && $category->children()->exists()) {
            foreach ($category->children as $child) {
                $childIds = array_merge($childIds, $this->getAllChildCategoryIds($child->id));
            }
        }

        return array_unique($childIds);
    }

    /**
     * Get products for this featured section based on a section type and categories.
     */
    public function getProductsQuery(?string $sort = null, array $storeIds = []): Builder
    {
        $query = Product::query();

        $categoryIds = $this->categories->pluck('id')->toArray() ?? [];
        // Handle scope-based filtering
        if ($this->scope_type === 'category' && $this->scope_id) {
            // Filter by the scope category and all its children
            $query->whereIn('category_id', $categoryIds);
        } elseif ($this->scope_type === 'global') {
            // For global scope, filter by categories if any are associated (legacy behavior)
            if ($this->categories->isNotEmpty()) {
                $query->whereIn('category_id', $this->categories->pluck('id'));
            }
        }
        // Apply filtering based on a section type
        switch ($this->section_type) {
            case FeaturedSectionTypeEnum::NEWLY_ADDED():
                $query->orderBy('created_at', 'desc');
                break;
            case FeaturedSectionTypeEnum::TOP_RATED():
                $query->withAvg('reviews', 'rating')
                    ->orderBy('reviews_avg_rating', 'desc');
                break;
            case FeaturedSectionTypeEnum::FEATURED():
                $query->where('featured', '1')
                    ->orderBy('created_at', 'desc');
                break;
            case FeaturedSectionTypeEnum::BEST_SELLER():
                $query->withCount('orderItems')
                    ->orderBy('order_items_count', 'desc');
                break;
        }

        // Apply explicit sorting if provided (supports price and other custom sorts)
        $query->applySorting($sort, $storeIds);

        return $query;
    }


    /**
     * Get products for this featured section.
     */
    public function products($limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->getProductsQuery();

        if ($limit) {
            return $query->take($limit)->get();
        }
        return $query->get();
    }

    /**
     * Scope to get only active featured sections.
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', ActiveInactiveStatusEnum::ACTIVE());
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to filter by section type.
     */
    public function scopeByType($query, $type): Builder
    {
        return $query->where('section_type', $type);
    }

    /**
     * Scope to filter by scope type.
     */
    public function scopeByScopeType($query, $scopeType): Builder
    {
        return $query->where('scope_type', $scopeType);
    }

    /**
     * Scope to get global featured sections.
     */
    public function scopeGlobal($query): Builder
    {
        return $query->where('scope_type', 'global');
    }

    /**
     * Scope to get category-specific featured sections.
     */
    public static function scopeByCategory($query, $categoryId = null): Builder
    {
        $query = $query->where('scope_type', 'category');

        if ($categoryId) {
            $query->where('scope_id', $categoryId);
        }

        return $query;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
