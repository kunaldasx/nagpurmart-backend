<?php

namespace App\Http\Requests\AttributeValue;

use App\Models\GlobalProductAttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attribute_id' => [
                'required',
                Rule::exists('global_product_attributes', 'id')
                    ->withoutTrashed(),
            ],
            'values' => 'required|array|min:1',
            'values.*' => [
                'required',
                'string',
                'max:255',
            ],
            'swatche_value' => 'required|array',
            'swatche_value.*' => 'required',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $normalizedValues = [];

            foreach ($this->input('values', []) as $index => $value) {
                if (!filled($value)) {
                    continue;
                }

                $title = trim((string) $value);
                $normalizedTitle = mb_strtolower($title);

                if (in_array($normalizedTitle, $normalizedValues, true)) {
                    $validator->errors()->add("values.$index", "Attribute value '{$title}' already exists for this attribute.");
                    continue;
                }

                $normalizedValues[] = $normalizedTitle;

                $exists = GlobalProductAttributeValue::query()
                    ->where('global_attribute_id', $this->input('attribute_id'))
                    ->whereRaw('LOWER(title) = ?', [$normalizedTitle])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("values.$index", "Attribute value '{$title}' already exists for this attribute.");
                }
            }
        });
    }
}
