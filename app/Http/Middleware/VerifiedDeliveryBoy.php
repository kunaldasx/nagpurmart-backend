<?php

namespace App\Http\Middleware;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Models\DeliveryBoy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifiedDeliveryBoy
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => []
            ], 401);
        }

        // Check if user is a delivery boy
        $deliveryBoy = DeliveryBoy::where('user_id', $user->id)->first();
        if (!$deliveryBoy) {
            return response()->json([
                'success' => false,
                'message' => __('labels.not_a_delivery_boy'),
                'data' => []
            ], 403);
        }

        // Check if delivery boy is verified
        if ($deliveryBoy->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return response()->json([
                'success' => false,
                'message' => __('labels.account_not_verified'),
                'data' => [
                    'verification_status' => $deliveryBoy->verification_status,
                    'verification_remark' => $deliveryBoy->verification_remark
                ]
            ], 403);
        }

        return $next($request);
    }
}
