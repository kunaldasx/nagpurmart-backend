<?php

namespace App\Http\Requests\Subscription;

use App\Enums\Payment\PaymentTypeEnum;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanBuyRequest extends FormRequest
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
        $planId = (int) $this->input('plan_id');
        $isFree = false;
        if ($planId) {
            $plan = SubscriptionPlan::find($planId);
            $isFree = $plan && (float)$plan->price <= 0 && (bool)$plan->status === true;
        }

        $paymentTypeRule = $isFree
            ? ['nullable', Rule::in(
                PaymentTypeEnum::RAZORPAY(),
                PaymentTypeEnum::STRIPE(),
                PaymentTypeEnum::PAYSTACK(),
                PaymentTypeEnum::WALLET(),
                PaymentTypeEnum::FLUTTERWAVE(),
            )]
            : ['required', Rule::in(
                PaymentTypeEnum::RAZORPAY(),
                PaymentTypeEnum::STRIPE(),
                PaymentTypeEnum::PAYSTACK(),
                PaymentTypeEnum::WALLET(),
                PaymentTypeEnum::FLUTTERWAVE(),
            )];

        return [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
            'payment_type' => $paymentTypeRule,
        ];
    }
}
