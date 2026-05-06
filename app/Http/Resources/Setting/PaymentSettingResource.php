<?php

namespace App\Http\Resources\Setting;

use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSettingResource extends JsonResource
{
    use PanelAware;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'variable' => $this->variable,
            'value' => [
                'stripePayment' => $this->value['stripePayment'] ?? false,
                'stripePaymentMode' => $this->value['stripePaymentMode'] ?? '',
                'stripePublishableKey' => $this->value['stripePublishableKey'] ?? '',
                'stripeCurrencyCode' => $this->value['stripeCurrencyCode'] ?? '',
                'razorpayPayment' => $this->value['razorpayPayment'] ?? false,
                'razorpayPaymentMode' => $this->value['razorpayPaymentMode'] ?? '',
                'razorpayKeyId' => $this->value['razorpayKeyId'] ?? '',
                'paystackPayment' => $this->value['paystackPayment'] ?? false,
                'paystackPaymentMode' => $this->value['paystackPaymentMode'] ?? '',
                'paystackPublicKey' => $this->value['paystackPublicKey'] ?? '',
                'wallet' => $this->value['walletPayment'] ?? false,
                'cod' => $this->value['cod'] ?? false,
                'directBankTransfer' => $this->value['directBankTransfer'] ?? false,
                'bankAccountName' => $this->value['bankAccountName'] ?? '',
                'bankAccountNumber' => $this->value['bankAccountNumber'] ?? '',
                'bankName' => $this->value['bankName'] ?? '',
                'bankCode' => $this->value['bankCode'] ?? '',
                'bankExtraNote' => $this->value['bankExtraNote'] ?? '',
                'flutterwavePayment' => $this->value['flutterwavePayment'] ?? false,
                'flutterwavePaymentMode' => $this->value['flutterwavePaymentMode'] ?? '',
                'flutterwavePublicKey' => $this->value['flutterwavePublicKey'] ?? '',
                'flutterwaveCurrencyCode' => $this->value['flutterwaveCurrencyCode'] ?? '',
            ]
        ];

        // Only include critical/sensitive data for admin panel
        if ($this->getPanel() === 'admin') {
            $data['value'] = array_merge($data['value'], [
                'stripeSecretKey' => $this->value['stripeSecretKey'] ?? '',
                'stripePaymentEndpointUrl' => $this->value['stripePaymentEndpointUrl'] ?? '',
                'stripeWebhookSecretKey' => $this->value['stripeWebhookSecretKey'] ?? '',
                'razorpaySecretKey' => $this->value['razorpaySecretKey'] ?? '',
                'razorpayWebhookSecret' => $this->value['razorpayWebhookSecret'] ?? '',
                'paystackSecretKey' => $this->value['paystackSecretKey'] ?? '',
                'paystackCurrencyCode' => $this->value['paystackCurrencyCode'] ?? '',
                'paystackWebhookSecret' => $this->value['paystackWebhookSecret'] ?? '',
                'flutterwaveSecretKey' => $this->value['flutterwaveSecretKey'] ?? '',
                'flutterwaveEncryptionKey' => $this->value['flutterwaveEncryptionKey'] ?? '',
                'flutterwaveWebhookSecret' => $this->value['flutterwaveWebhookSecret'] ?? '',
            ]);
        }

        return $data;
    }
}
