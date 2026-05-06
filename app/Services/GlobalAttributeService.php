<?php

namespace App\Services;

use App\Models\GlobalProductAttribute;

class GlobalAttributeService
{
    public static function getAttributesWithValue($sellerId = null)
    {
        return GlobalProductAttribute::with('values')->when($sellerId, fn($query) => $query->where('seller_id', $sellerId))->get()->mapWithKeys(function ($attr) {
            return [
                strtolower($attr->title) => [
                    'id' => $attr->id,
                    'name' => $attr->title,
                    'values' => $attr->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->title,
                        ];
                    })->toArray()
                ]
            ];
        });
    }
}
