<?php

namespace App\Services;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Models\DeliveryBoy;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliveryBoyLoginService
{
    public function durationHours(): int
    {
        $value = Setting::query()->find('delivery_boy')?->value['riderLoginDurationHours'] ?? 24;

        return is_numeric($value) && (int) $value >= 1 && (int) $value <= 720
            ? (int) $value
            : 24;
    }

    public function issueOrGetQr(DeliveryBoy $deliveryBoy): array
    {
        return DB::transaction(function () use ($deliveryBoy): array {
            $locked = DeliveryBoy::query()->lockForUpdate()->findOrFail($deliveryBoy->id);
            $token = $locked->qr_login_token_encrypted
                && $locked->qr_login_expires_at?->isFuture()
                ? Crypt::decryptString($locked->qr_login_token_encrypted)
                : Str::random(64);

            if (!$locked->qr_login_expires_at?->isFuture()) {
                $locked->update([
                    'qr_login_token_hash' => hash('sha256', $token),
                    'qr_login_token_encrypted' => Crypt::encryptString($token),
                    'qr_login_expires_at' => now()->addHours($this->durationHours()),
                ]);
            }

            return [
                'token' => $token,
                'expires_at' => $locked->fresh()->qr_login_expires_at,
            ];
        });
    }

    public function rotateQr(DeliveryBoy $deliveryBoy, ?int $durationHours = null): array
    {
        $durationHours ??= $this->durationHours();
        if ($durationHours < 1 || $durationHours > $this->durationHours()) {
            throw new \InvalidArgumentException('QR duration must be within the platform-configured limit.');
        }

        return DB::transaction(function () use ($deliveryBoy, $durationHours): array {
            $locked = DeliveryBoy::query()->lockForUpdate()->findOrFail($deliveryBoy->id);
            $token = Str::random(64);
            $expiresAt = now()->addHours($durationHours);

            $locked->update([
                'qr_login_token_hash' => hash('sha256', $token),
                'qr_login_token_encrypted' => Crypt::encryptString($token),
                'qr_login_expires_at' => $expiresAt,
            ]);
            $locked->user?->tokens()->where('name', 'rider-qr')->delete();

            return [
                'token' => $token,
                'expires_at' => $expiresAt,
            ];
        });
    }

    public function findValidQrRider(string $token): ?DeliveryBoy
    {
        $deliveryBoy = DeliveryBoy::query()
            ->with('user')
            ->where('qr_login_token_hash', hash('sha256', $token))
            ->where('qr_login_expires_at', '>', now())
            ->first();

        if (!$deliveryBoy
            || !$deliveryBoy->user
            || $deliveryBoy->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return null;
        }

        return $deliveryBoy;
    }

    public function requestPasswordApproval(DeliveryBoy $deliveryBoy): void
    {
        if ($deliveryBoy->login_approval_status === 'approved'
            && $deliveryBoy->login_approved_until?->isFuture()) {
            return;
        }

        if ($deliveryBoy->login_approval_status !== 'pending') {
            $deliveryBoy->update([
                'login_approval_status' => 'pending',
                'login_approval_requested_at' => now(),
                'login_approved_at' => null,
                'login_approved_until' => null,
            ]);

            $seller = $deliveryBoy->seller()->with('user')->first();
            if ($seller?->user) {
                try {
                    app(NotificationService::class)->notifySellerRiderLoginRequest(
                        $seller->user,
                        $deliveryBoy->user,
                        (int) $seller->id,
                        (int) $deliveryBoy->id,
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Failed to notify seller of rider login request', [
                        'delivery_boy_id' => $deliveryBoy->id,
                        'seller_id' => $seller->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }

    public function approvePasswordLogin(DeliveryBoy $deliveryBoy): void
    {
        $approvedAt = now();
        $deliveryBoy->update([
            'login_approval_status' => 'approved',
            'login_approved_at' => $approvedAt,
            'login_approved_until' => $approvedAt->copy()->addHours($this->durationHours()),
        ]);
    }

    public function rejectPasswordLogin(DeliveryBoy $deliveryBoy): void
    {
        $deliveryBoy->update([
            'login_approval_status' => 'disapproved',
            'login_approved_at' => null,
            'login_approved_until' => null,
        ]);
        $deliveryBoy->user?->tokens()->where('name', 'rider-password')->delete();
    }
}
