<?php

namespace App\Http\Requests\Seller;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Enum;

class UpdateSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zipcode' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'country_code' => 'sometimes|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'business_license' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'articles_of_incorporation' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'national_identity_card' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'authorized_signature' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'verification_status' => ['required', new Enum(SellerVerificationStatusEnum::class)],
            'visibility_status' => ['required', new Enum(SellerVisibilityStatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes' => __('validation.sometimes', ['attribute' => 'Name']),
            'address.required' => __('validation.required', ['attribute' => 'Address']),
            'city.required' => __('validation.required', ['attribute' => 'City']),
            'state.required' => __('validation.required', ['attribute' => 'State']),
            'zipcode.required' => __('validation.required', ['attribute' => 'Zipcode']),
            'country.required' => __('validation.required', ['attribute' => 'Country']),
            'business_license.image' => __('validation.image', ['attribute' => 'Business License']),
            'articles_of_incorporation.image' => __('validation.image', ['attribute' => 'Articles of Incorporation']),
            'national_identity_card.image' => __('validation.image', ['attribute' => 'National Identity Card']),
            'authorized_signature.image' => __('validation.image', ['attribute' => 'Authorized Signature']),
        ];
    }
}
