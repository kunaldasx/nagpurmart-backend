<?php

namespace App\Http\Requests\DeliveryBoy;

use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnPickupStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:' . OrderItemReturnPickupStatusEnum::PICKED_UP() . ',' . OrderItemReturnPickupStatusEnum::DELIVERED_TO_SELLER()],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('validation.status_required'),
            'status.in' => __('validation.status_in'),
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'pickup status',
        ];
    }
}
