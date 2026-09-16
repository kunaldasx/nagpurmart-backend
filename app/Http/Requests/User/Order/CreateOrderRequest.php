<?php

namespace App\Http\Requests\User\Order;

use App\Enums\Payment\PaymentTypeEnum;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\DeliveryTimeSlot;
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
            'order_mode' => ['nullable', Rule::in(['regular', 'wholesale'])],
            'delivery_time_slot_id' => ['nullable', 'integer', 'exists:delivery_time_slots,id'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
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

        if ($this->input('order_mode') === 'wholesale') {
            $rules['delivery_time_slot_id'][] = 'required';
            $rules['delivery_date'][] = 'required';
        }

        return $rules;
    }

    /**
     * Configure the validator instance to enforce required attachments for products that require them.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('order_mode') !== 'wholesale' || !$this->filled('delivery_time_slot_id') || !$this->filled('delivery_date')) {
                return;
            }

            $slot = DeliveryTimeSlot::whereKey($this->integer('delivery_time_slot_id'))
                ->where('is_active', true)
                ->first();
            $date = $this->date('delivery_date');
            if (!$slot || strtolower($date->format('l')) !== strtolower($slot->day_of_week)) {
                $validator->errors()->add('delivery_time_slot_id', 'The selected delivery slot is not available for the selected date.');
                return;
            }

            if (Order::where('delivery_time_slot_id', $slot->id)
                ->whereDate('delivery_date', $date)
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->count() >= $slot->max_orders) {
                $validator->errors()->add('delivery_time_slot_id', 'The selected delivery slot is full.');
            }

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
