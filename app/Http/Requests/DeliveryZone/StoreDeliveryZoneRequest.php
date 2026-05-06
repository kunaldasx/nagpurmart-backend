<?php

namespace App\Http\Requests\DeliveryZone;

use App\Enums\ActiveInactiveStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDeliveryZoneRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:delivery_zones,name',
            'center_latitude' => 'required|numeric|between:-90,90',
            'center_longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1',
            'delivery_time_per_km'=> 'required|numeric|min:0',
            'buffer_time' => 'required|numeric|min:0',
            'boundary_json' => 'nullable|json',
            'status' => ['nullable', new Enum(ActiveInactiveStatusEnum::class)],
            'rush_delivery_enabled' => 'boolean',
            'rush_delivery_time_per_km' => 'required_if:rush_delivery_enabled,true|numeric|min:0',
            'rush_delivery_charges' => 'required_if:rush_delivery_enabled,true|numeric|min:0',
            'regular_delivery_charges' => 'required|numeric|min:0',
            'free_delivery_amount' => 'nullable|numeric|min:0',
            'distance_based_delivery_charges' => 'nullable|numeric|min:0',
            'per_store_drop_off_fee' => 'nullable|numeric|min:0',
            'handling_charges' => 'nullable|numeric|min:0',
            'delivery_boy_base_fee' => 'nullable|numeric|min:0',
            'delivery_boy_per_store_pickup_fee' => 'nullable|numeric|min:0',
            'delivery_boy_distance_based_fee' => 'nullable|numeric|min:0',
            'delivery_boy_per_order_incentive' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.delivery_zone.name.required'),
            'name.unique' => __('validation.delivery_zone.name.unique'),
            'center_latitude.required' => __('validation.delivery_zone.center_latitude.required'),
            'center_latitude.between' => __('validation.delivery_zone.center_latitude.between'),
            'center_longitude.required' => __('validation.delivery_zone.center_longitude.required'),
            'center_longitude.between' => __('validation.delivery_zone.center_longitude.between'),
            'radius_km.required' => __('validation.delivery_zone.radius_km.required'),
            'radius_km.min' => __('validation.delivery_zone.radius_km.min'),
            'radius_km.max' => __('validation.delivery_zone.radius_km.max'),
            'boundary_json.json' => __('validation.delivery_zone.boundary_json.json'),
            'status.in' => __('validation.delivery_zone.status.in'),
        ];

    }
}
