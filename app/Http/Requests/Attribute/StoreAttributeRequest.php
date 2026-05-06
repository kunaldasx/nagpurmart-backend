<?php

namespace App\Http\Requests\Attribute;

use App\Enums\Attribute\AttributeTypesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = auth()->user();
        $sellerId = $user ? optional($user->seller())->id : null;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('global_product_attributes', 'title')
                    ->where(function ($q) use ($sellerId) {
                        if ($sellerId) {
                            $q->where('seller_id', $sellerId);
                        }
                        // Exclude soft-deleted records
                        $q->whereNull('deleted_at');
                    }),
            ],
            'label' => 'required|string',
            'swatche_type' => ['nullable', new Enum(AttributeTypesEnum::class)],
        ];
    }
}
