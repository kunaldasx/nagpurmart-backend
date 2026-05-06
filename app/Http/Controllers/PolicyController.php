<?php

namespace App\Http\Controllers;

use App\Enums\PoliciesEnum;
use App\Enums\SettingTypeEnum;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class PolicyController extends Controller
{
    public function show(PoliciesEnum $policy): View
    {
        // Determine which setting group and key to read based on the enum
        $mapping = [
            PoliciesEnum::REFUND_AND_RETURN() => [SettingTypeEnum::WEB(), 'returnRefundPolicy', __('labels.return_refund_policy')],
            PoliciesEnum::SHIPPING() => [SettingTypeEnum::WEB(), 'shippingPolicy', __('labels.shipping_policy')],
            PoliciesEnum::PRIVACY() => [SettingTypeEnum::WEB(), 'privacyPolicy', __('labels.privacy_policy')],
            PoliciesEnum::TERMS() => [SettingTypeEnum::WEB(), 'termsCondition', __('labels.terms_condition')],
            PoliciesEnum::ABOUTUS() => [SettingTypeEnum::WEB(), 'aboutUs', __('labels.about_us')],
            PoliciesEnum::DELIVERY_PRIVACY() => [SettingTypeEnum::DELIVERY_BOY(), 'privacyPolicy', __('labels.delivery_boy_privacy_policy')],
            PoliciesEnum::DELIVERY_TERMS() => [SettingTypeEnum::DELIVERY_BOY(), 'termsCondition', __('labels.delivery_boy_terms_condition')],
            PoliciesEnum::SELLER_PRIVACY() => [SettingTypeEnum::SELLER(), 'privacyPolicy', __('labels.seller_privacy_policy')],
            PoliciesEnum::SELLER_TERMS() => [SettingTypeEnum::SELLER(), 'termsCondition', __('labels.seller_terms_condition')],
        ];

        [$variable, $key, $title] = $mapping[$policy->value] ?? [SettingTypeEnum::WEB()->value, 'termsCondition', __('labels.terms_condition')];

        $setting = Setting::where('variable', $variable)->first();
        $value = $setting?->value ?? [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = [];
            }
        }

        $content = $value[$key] ?? '';

        return view('policy.show', [
            'title' => $title,
            'content' => $content,
        ]);
    }
}
