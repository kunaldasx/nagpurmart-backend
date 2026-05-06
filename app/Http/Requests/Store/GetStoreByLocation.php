<?php

namespace App\Http\Requests\Store;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetStoreByLocation extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => __('messages.latitude_required'),
            'latitude.numeric' => __('messages.latitude_numeric'),
            'latitude.between' => __('messages.latitude_between'),
            'longitude.required' => __('messages.longitude_required'),
            'longitude.numeric' => __('messages.longitude_numeric'),
            'longitude.between' => __('messages.longitude_between'),
        ];
    }
}
