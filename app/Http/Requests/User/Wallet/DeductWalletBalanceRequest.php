<?php

namespace App\Http\Requests\User\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class DeductWalletBalanceRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'description' => ['nullable', 'string', 'max:255'],
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
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
            'order_id.exists' => 'The selected order does not exist.',
            'store_id.exists' => 'The selected store does not exist.',
        ];
    }
}
