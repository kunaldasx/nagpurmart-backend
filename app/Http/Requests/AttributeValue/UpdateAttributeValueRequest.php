<?php

namespace App\Http\Requests\AttributeValue;

use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'attribute_id' => [
                'required',
                Rule::exists('global_product_attributes', 'id')
                    ->withoutTrashed(),
            ],
            'values' => 'nullable|array',
            'values.*' => [
                'nullable',
                'string',
                'max:255',
            ],
            'swatche_value' => 'nullable|array',
        ];

        $attribute = GlobalProductAttribute::find($this->attribute_id);
        if ($attribute && $attribute->swatche_type === 'text') {
            $rules['swatche_value'] = 'required|array';
            $rules['swatche_value.*'] = 'required';
        } else {
            $rules['swatche_value.*'] = 'nullable';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $normalizedValues = [];
            $id = $this->route('id');

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
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("values.$index", "Attribute value '{$title}' already exists for this attribute.");
                }
            }
        });
    }
}
