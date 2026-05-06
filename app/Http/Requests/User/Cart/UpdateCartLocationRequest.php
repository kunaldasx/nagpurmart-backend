<?php

namespace App\Http\Requests\User\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartLocationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => __('validation.latitude_required'),
            'latitude.numeric' => __('validation.latitude_numeric'),
            'latitude.between' => __('validation.latitude_between'),
            'longitude.required' => __('validation.longitude_required'),
            'longitude.numeric' => __('validation.longitude_numeric'),
            'longitude.between' => __('validation.longitude_between')
        ];
    }
}
