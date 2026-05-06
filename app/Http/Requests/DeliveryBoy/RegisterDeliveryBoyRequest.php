<?php

namespace App\Http\Requests\DeliveryBoy;

use App\Enums\DeliveryBoy\DeliveryBoyVehicleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterDeliveryBoyRequest extends FormRequest
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
            'email' => 'required|string|email|unique:users',
            'mobile' => 'required|unique:users|numeric',
            'password' => 'required|string|min:6|confirmed',
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'driver_license_number' => 'required|string|max:255',
            'vehicle_type' => ['required', new Enum(DeliveryBoyVehicleTypeEnum::class)],
            'delivery_zone_id' => 'required|exists:delivery_zones,id',
            'driver_license.*' => 'required|image|max:2048',
            'vehicle_registration.*' => 'required|image|max:2048',
            'country' => 'nullable|string|max:255',
            'iso_2' => 'nullable|string|max:2'
        ];
    }
}
