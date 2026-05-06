<?php

namespace App\Http\Requests\User\Wallet;

use App\Enums\Payment\PaymentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareWalletRechargeRequest extends FormRequest
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
        $rules = [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(  PaymentTypeEnum::RAZORPAY(), PaymentTypeEnum::STRIPE(), PaymentTypeEnum::PAYSTACK(), PaymentTypeEnum::FLUTTERWAVE())],
            'description' => ['nullable', 'string', 'max:255'],
            'redirect_url' => ['nullable'],
        ];
        if (!empty($this->input('redirect_url'))) {
            $rules['redirect_url'] = ['required', 'url'];
        }
        return $rules;
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
            'payment_method.required' => 'The payment method field is required.',
            'payment_method.max' => 'The payment method must not exceed 50 characters.',
        ];
    }
}
