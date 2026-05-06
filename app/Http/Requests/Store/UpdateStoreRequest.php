<?php

namespace App\Http\Requests\Store;

use App\Enums\BankAccountTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_number' => 'required|numeric|digits_between:7,15',
            'address' => 'required|string|max:500',
            'landmark' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zipcode' => 'required|string|max:20',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'store_logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:1024',
            'store_banner' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:1024',
            'country' => 'required|string|max:100',
            'tax_name' => 'required|string|max:255',
            'tax_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_branch_code' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|numeric',
            'routing_number' => 'required|numeric',
            'bank_account_type' => ['required', new Enum(BankAccountTypeEnum::class)],
        ];
    }
}
