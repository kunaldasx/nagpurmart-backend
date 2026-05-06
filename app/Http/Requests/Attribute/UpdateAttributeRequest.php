<?php

namespace App\Http\Requests\Attribute;

use App\Enums\Attribute\AttributeTypesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sellerId = optional(auth()->user())->seller()->id ?? null;
        $id = $this->route('id');

        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('global_product_attributes', 'title')
                    ->ignore($id)
                    ->where(function ($q) use ($sellerId) {
                        if ($sellerId) {
                            $q->where('seller_id', $sellerId);
                        }
                        $q->whereNull('deleted_at');
                    }),
            ],
            'label' => 'required|string',
            'swatche_type' => ['nullable', new Enum(AttributeTypesEnum::class)],
        ];
    }
}
