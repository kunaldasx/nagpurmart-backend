<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\SellerUser;
use App\Models\User;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SellerUserLoginApprovalService
{
    public function requestApprovalIfRequired(User $user): ?JsonResponse
    {
        $membership = SellerUser::where('user_id', $user->id)->first();
        if (!$membership || $membership->login_approval_status === 'approved') {
            return null;
        }

        $wasAlreadyPending = $membership->login_approval_status === 'pending';
        if (!$wasAlreadyPending) {
            $membership->update([
                'login_approval_status' => 'pending',
                'login_approval_requested_at' => now(),
            ]);

            try {
                $seller = Seller::with('user')->find($membership->seller_id);
                if ($seller?->user) {
                    app(NotificationService::class)->notifySellerSystemUserLoginRequest(
                        $seller->user,
                        $user,
                        (int) $seller->id,
                    );

                    $firebase = app(FirebaseService::class);
                    foreach ($seller->user->fcmTokens()->pluck('fcm_token')->filter() as $token) {
                        $firebase->sendNotification(
                            $token,
                            'System user login approval required',
                            $user->name . ' is requesting access to your seller account.',
                            data: [
                                'type' => 'seller_system_user_login_approval',
                                'system_user_id' => (string) $user->id,
                                'seller_id' => (string) $seller->id,
                            ],
                        );
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('Failed to notify seller of system user login request', [
                    'system_user_id' => $user->id,
                    'seller_id' => $membership->seller_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return ApiResponseType::sendJsonResponse(
            success: false,
            message: 'Your login request is waiting for seller approval.',
            data: [
                'login_approval_status' => 'pending',
                'approval_requested_at' => $membership->fresh()->login_approval_requested_at,
            ],
            status: 403,
        );
    }
}