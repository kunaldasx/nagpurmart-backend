<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\GuardNameEnum;
use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\DeliveryBoy;
use App\Models\DeliveryZone;
use App\Models\Seller;
use App\Models\User;
use App\Services\DeliveryBoyLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeliveryBoyController extends Controller
{
    public function index(): View
    {
        $seller = $this->sellerForPermission('delivery_boy.view');
        $riders = DeliveryBoy::query()
            ->with('user:id,name,email,mobile')
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->paginate(15);

        return view('seller.delivery_boys.index', [
            'riders' => $riders,
            'deliveryZones' => DeliveryZone::query()
                ->where('status', ActiveInactiveStatusEnum::ACTIVE())
                ->orderBy('name')
                ->get(['id', 'name']),
            'loginDurationHours' => app(DeliveryBoyLoginService::class)->durationHours(),
            'canManageRiders' => $this->hasPermission('delivery_boy.manage', $seller),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $seller = $this->sellerForPermission('delivery_boy.manage');
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'mobile' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'delivery_zone_id' => 'required|integer|exists:delivery_zones,id,status,active',
        ]);

        DB::transaction(function () use ($seller, $validated): void {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => true,
                'access_panel' => GuardNameEnum::WEB(),
            ]);

            DeliveryBoy::create([
                'user_id' => $user->id,
                'seller_id' => $seller->id,
                'status' => 'inactive',
                'full_name' => $validated['full_name'],
                'delivery_zone_id' => $validated['delivery_zone_id'],
                'verification_status' => DeliveryBoyVerificationStatusEnum::PENDING(),
                'login_approval_status' => 'disapproved',
            ]);
        });

        return redirect()->route('seller.delivery-boys.index')
            ->with('success', 'Rider account created. Platform verification is required before the rider can log in.');
    }

    public function showQr(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        if ($rider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return response()->json([
                'success' => false,
                'message' => 'Rider must be verified before QR login can be enabled.',
            ], 422);
        }

        $qr = $loginService->issueOrGetQr($rider);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_payload' => $qr['token'],
                'expires_at' => $qr['expires_at'],
            ],
        ]);
    }

    public function rotateQr(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        if ($rider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return response()->json([
                'success' => false,
                'message' => 'Rider must be verified before QR login can be enabled.',
            ], 422);
        }

        $qr = $loginService->rotateQr($rider);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_payload' => $qr['token'],
                'expires_at' => $qr['expires_at'],
            ],
        ]);
    }

    public function approveLogin(int $id, DeliveryBoyLoginService $loginService): RedirectResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        $loginService->approvePasswordLogin($rider);

        return redirect()->route('seller.delivery-boys.index')
            ->with('success', 'Rider password login approved for ' . $loginService->durationHours() . ' hours.');
    }

    public function rejectLogin(int $id, DeliveryBoyLoginService $loginService): RedirectResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        $loginService->rejectPasswordLogin($rider);

        return redirect()->route('seller.delivery-boys.index')
            ->with('success', 'Rider password login disapproved.');
    }

    private function findSellerRider(int $id, string $permission): DeliveryBoy
    {
        $seller = $this->sellerForPermission($permission);

        return DeliveryBoy::query()
            ->with('user')
            ->where('seller_id', $seller->id)
            ->findOrFail($id);
    }

    private function sellerForPermission(string $permission): Seller
    {
        $user = auth()->user();
        $seller = $user?->seller();
        abort_unless($seller, 404, 'Seller account not found.');
        abort_unless($this->hasPermission($permission, $seller), 403, 'Permission denied.');

        return $seller;
    }

    private function hasPermission(string $permission, Seller $seller): bool
    {
        $user = auth()->user();
        if ((int) $seller->user_id === (int) $user?->id) {
            return true;
        }

        try {
            return (bool) $user?->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }
}
