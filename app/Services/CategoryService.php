<?php

namespace App\Services;

use App\Enums\CategoryStatusEnum;
use App\Models\Category;

class CategoryService
{
    public static function getCategoriesWithParent()
    {
        return Category::select('id', 'parent_id', 'title', 'requires_approval')->where('status', CategoryStatusEnum::ACTIVE())->get()->map(function ($category) {
            return [
                'id' => (string) $category->id,
                'parent' => $category->parent_id ? (string) $category->parent_id : '#',
                'text' => $category->title . ($category->requires_approval ? ' <small class="text-azure">(Requires Admin Approval)</small>' : ''),
            ];
        });
    }
}
