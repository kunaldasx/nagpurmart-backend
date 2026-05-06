<?php

namespace App\Http\Requests\User\Order;

use App\Enums\Payment\PaymentTypeEnum;
use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateOrderRequest extends FormRequest
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
            'payment_type' => ['required', Rule::in(PaymentTypeEnum::values())],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'gift_card' => ['nullable', 'string', 'max:50'],
            'address_id' => ['required', 'numeric', 'exists:addresses,id'],
            'rush_delivery' => ['boolean', 'nullable'],
            'use_wallet' => ['boolean', 'nullable'],
            'order_note' => ['nullable', 'string', 'max:500'],
            'redirect_url' => ['nullable'],

            // Attachments structure: attchment[productId][] or attachments[productId][]
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['array'],
            'attachments.*.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ];

        if (in_array($this->input('payment_type'), [PaymentTypeEnum::STRIPE(), PaymentTypeEnum::RAZORPAY(), PaymentTypeEnum::PAYSTACK()])) {
            $rules['transaction_id'] = ['required', 'string'];
        }
        if (!empty($this->input('redirect_url'))) {
            $rules['redirect_url'] = ['required', 'url'];
        }
        if ($this->input('payment_type') === PaymentTypeEnum::RAZORPAY()) {
            $rules['razorpay_order_id'] = ['required', 'string'];
            $rules['razorpay_signature'] = ['required', 'string'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance to enforce required attachments for products that require them.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            if (!$user) {
                return; // Authorization handled elsewhere
            }

            // Load user's cart with products to check requirement
            $cart = CartService::getUserCart($user);
            if (!$cart) {
                return;
            }

            $attachments = $this->file('attachments', []);
            $attachmentsAlt = $this->file('attchment', []); // alternate key

            foreach ($cart->items as $item) {
                $product = $item->product;
                if (!$product) {
                    continue;
                }
                $requires = (string)$product->is_attachment_required === '1' || $product->is_attachment_required === 1 || $product->is_attachment_required === true;
                if ($requires) {
                    $productId = (string)$product->id;
                    $files = [];
                    if (isset($attachments[$productId])) {
                        $files = (array)$attachments[$productId];
                    } elseif (isset($attachmentsAlt[$productId])) {
                        $files = (array)$attachmentsAlt[$productId];
                    }
                    if (empty($files)) {
                        $validator->errors()->add('attachments.' . $productId, __('validation.required', ['attribute' => 'attachment for product ' . $product->title]));
                    }
                }
            }
        });
    }
}
