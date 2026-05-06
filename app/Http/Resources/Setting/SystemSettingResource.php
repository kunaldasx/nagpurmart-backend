<?php

namespace App\Http\Resources\Setting;

use App\Enums\DateRangeFilterEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\SystemVendorTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'variable' => $this->variable,
            'value' => [
                'appName' => $this->value['appName'] ?? '',
                'sellerSupportNumber' => $this->value['sellerSupportNumber'] ?? '',
                'sellerSupportEmail' => $this->value['sellerSupportEmail'] ?? '',
                'systemTimezone' => $this->value['systemTimezone'] ?? '',
                'copyrightDetails' => $this->value['copyrightDetails'] ?? '',
                'logo' => !empty($this->value['logo']) ? url('storage/' . $this->value['logo']) : '',
                'favicon' => !empty($this->value['favicon']) ? url('storage/' . $this->value['favicon']) : '',
                'companyAddress' => $this->value['companyAddress'] ?? '',
                'adminSignature' => !empty($this->value['adminSignature']) ? url('storage/' . $this->value['adminSignature']) : '',
                'enableThirdPartyStoreSync' => $this->value['enableThirdPartyStoreSync'] ?? false,
                'Shopify' => $this->value['Shopify'] ?? false,
                'Woocommerce' => $this->value['Woocommerce'] ?? false,
                'etsy' => $this->value['etsy'] ?? false,
                'systemVendorType' => $this->value['systemVendorType'] ?? '',
                'checkoutType' => $this->value['checkoutType'] ?? 'multi_store',
                'minimumCartAmount' => $this->value['minimumCartAmount'] ?? 0.0,
                'maximumItemsAllowedInCart' => $this->value['maximumItemsAllowedInCart'] ?? 0.0,
                'lowStockLimit' => $this->value['lowStockLimit'] ?? '',
                'maximumDistanceToNearestStore' => $this->value['maximumDistanceToNearestStore'] ?? '',
                'enableWallet' => $this->value['enableWallet'] ?? false,
                'welcomeWalletBalanceAmount' => $this->value['welcomeWalletBalanceAmount'] ?? 0.0,
                'sellerAppMaintenanceMode' => $this->value['sellerAppMaintenanceMode'] ?? false,
                'sellerAppMaintenanceMessage' => $this->value['sellerAppMaintenanceMessage'] ?? '',
                'webMaintenanceMode' => $this->value['webMaintenanceMode'] ?? false,
                'webMaintenanceMessage' => $this->value['webMaintenanceMessage'] ?? '',
                // Demo mode
                'demoMode' => $this->value['demoMode'] ?? false,
                'adminDemoModeMessage' => $this->value['adminDemoModeMessage'] ?? '',
                'sellerDemoModeMessage' => $this->value['sellerDemoModeMessage'] ?? '',
                'customerDemoModeMessage' => $this->value['customerDemoModeMessage'] ?? '',
                'customerLocationDemoModeMessage' => $this->value['customerLocationDemoModeMessage'] ?? '',
                'deliveryBoyDemoModeMessage' => $this->value['deliveryBoyDemoModeMessage'] ?? '',
                'referEarnStatus' => $this->value['referEarnStatus'] ?? false,
                'referEarnMethodUser' => $this->value['referEarnMethodUser'] ?? '',
                'referEarnBonusUser' => $this->value['referEarnBonusUser'] ?? '',
                'referEarnMaximumBonusAmountUser' => $this->value['referEarnMaximumBonusAmountUser'] ?? '',
                'referEarnMethodReferral' => $this->value['referEarnMethodReferral'] ?? '',
                'referEarnBonusReferral' => $this->value['referEarnBonusReferral'] ?? '',
                'referEarnMaximumBonusAmountReferral' => $this->value['referEarnMaximumBonusAmountReferral'] ?? '',
                'referEarnMinimumOrderAmount' => $this->value['referEarnMinimumOrderAmount'] ?? '',
                'referEarnNumberOfTimesBonus' => $this->value['referEarnNumberOfTimesBonus'] ?? '',
                'currency' => $this->value['currency'] ?? 'USD',
                'currencySymbol' => $this->value['currencySymbol'] ?? '$',
                'notificationType' => NotificationTypeEnum::values() ?? [],
                'dataFilterEnum' => DateRangeFilterEnum::values() ?? [],
                'orderStatusEnum' => OrderStatusEnum::orderFilters() ?? [],
            ]
        ];
    }
}
