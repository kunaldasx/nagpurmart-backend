<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => [
                'nullable',
                'numeric',
                Rule::requiredIf(fn () => !$this->boolean('is_free')),
                Rule::when(!$this->boolean('is_free'), ['min:1']),
            ],
            'duration_type' => 'required|in:unlimited,days',
            'duration_days' => 'nullable|integer|min:1|required_if:duration_type,days',
            'is_free' => 'sometimes|boolean',
            'is_recommended' => 'sometimes|boolean',
            'status' => 'sometimes|boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_free' => $this->boolean('is_free'),
            'is_recommended' => $this->boolean('is_recommended'),
            'status' => $this->boolean('status'),
        ]);
    }

    /**
     * Modify validated data before returning.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if (($data['duration_type'] ?? 'unlimited') === 'unlimited') {
            $data['duration_days'] = null;
        }
        if (($data['is_free'] ?? false) === true) {
            $data['price'] = 0;
        }

        return $data;
    }
}
