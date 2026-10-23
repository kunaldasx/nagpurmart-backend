<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\GuardNameEnum;
use App\Http\Controllers\Controller;
use App\Models\DeliveryBoy;
use App\Models\Seller;
use App\Models\User;
use App\Services\DeliveryBoyLoginService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SellerDeliveryBoyApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $seller = $this->sellerForPermission('delivery_boy.view');
        $query = DeliveryBoy::query()
            ->with('user:id,name,email,mobile')
            ->where('seller_id', $seller->id)
            ->orderByDesc('id');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('user', function ($users) use ($search) {
                $users->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $riders = $query->paginate(min(max((int) $request->input('per_page', 15), 1), 100));
        $availableQuery = DeliveryBoy::query()->whereRaw('1 = 0');
        if ($this->sellerHasPermission('delivery_boy.manage', $seller)) {
            $availableQuery = DeliveryBoy::query()
                ->with(['user:id,name,email,mobile', 'deliveryZone:id,name'])
                ->whereNull('seller_id')
                ->orderByDesc('id');

            if ($search) {
                $availableQuery->whereHas('user', function ($users) use ($search) {
                    $users->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            }
        }

        $availableRiders = $availableQuery->paginate(
            min(max((int) $request->input('per_page', 15), 1), 100),
            ['*'],
            'available_page'
        );

        return ApiResponseType::sendJsonResponse(true, 'Riders retrieved.', [
            'assigned_riders' => $this->riderPaginator($riders),
            'available_riders' => $this->riderPaginator($availableRiders),
        ]);
    }

    public function claim(int $id): JsonResponse
    {
        $seller = $this->sellerForPermission('delivery_boy.manage');
        $claimed = DB::transaction(function () use ($id, $seller): ?DeliveryBoy {
            $rider = DeliveryBoy::query()->with('user')->lockForUpdate()->findOrFail($id);
            if ($rider->seller_id !== null) {
                return null;
            }

            $rider->update([
                'seller_id' => $seller->id,
                'login_approval_status' => 'disapproved',
                'login_approval_requested_at' => null,
                'login_approved_at' => null,
                'login_approved_until' => null,
                'qr_login_token_hash' => null,
                'qr_login_token_encrypted' => null,
                'qr_login_expires_at' => null,
            ]);
            $rider->user?->tokens()->delete();

            return $rider->fresh(['user:id,name,email,mobile']);
        });

        if (!$claimed) {
            return ApiResponseType::sendJsonResponse(
                false,
                'This delivery boy has already been assigned to a seller.',
                ['code' => 'rider_already_assigned'],
                409
            );
        }

        return ApiResponseType::sendJsonResponse(true, 'Existing delivery boy added to your seller account.', [
            'rider' => $this->riderData($claimed),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $seller = $this->sellerForPermission('delivery_boy.manage');
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'mobile' => 'nullable|string|max:30',
            'password' => 'required|string|min:8',
            'delivery_zone_id' => 'required|integer|exists:delivery_zones,id,status,active',
        ]);

        $rider = DB::transaction(function () use ($seller, $validated): DeliveryBoy {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => true,
                'access_panel' => GuardNameEnum::WEB(),
            ]);

            return DeliveryBoy::create([
                'user_id' => $user->id,
                'seller_id' => $seller->id,
                'delivery_zone_id' => $validated['delivery_zone_id'],
                'status' => 'inactive',
                'full_name' => $validated['full_name'],
                'verification_status' => DeliveryBoyVerificationStatusEnum::PENDING(),
                'login_approval_status' => 'disapproved',
            ]);
        });

        return ApiResponseType::sendJsonResponse(true, 'Rider created. Platform verification is required before login.', [
            'rider' => $this->riderData($rider->load('user:id,name,email,mobile')),
        ], 201);
    }

    public function showQr(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        if ($rider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return ApiResponseType::sendJsonResponse(false, 'Rider must be verified before QR login can be enabled.', [
                'verification_status' => $rider->verification_status,
            ], 422);
        }

        $qr = $loginService->issueOrGetQr($rider);

        return ApiResponseType::sendJsonResponse(true, 'Rider QR login credential retrieved.', [
            'rider_id' => $rider->id,
            'qr_token' => $qr['token'],
            'qr_payload' => $qr['token'],
            'expires_at' => $qr['expires_at'],
        ]);
    }

    public function rotateQr(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        if ($rider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
            return ApiResponseType::sendJsonResponse(false, 'Rider must be verified before QR login can be enabled.', [
                'verification_status' => $rider->verification_status,
            ], 422);
        }

        $qr = $loginService->rotateQr($rider);

        return ApiResponseType::sendJsonResponse(true, 'Rider QR credential rotated.', [
            'rider_id' => $rider->id,
            'qr_token' => $qr['token'],
            'qr_payload' => $qr['token'],
            'expires_at' => $qr['expires_at'],
        ]);
    }

    public function approveLogin(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        $loginService->approvePasswordLogin($rider);

        return ApiResponseType::sendJsonResponse(true, 'Rider password login approved.', [
            'rider_id' => $rider->id,
            'login_approval_status' => $rider->fresh()->login_approval_status,
            'login_approved_until' => $rider->fresh()->login_approved_until,
        ]);
    }

    public function rejectLogin(int $id, DeliveryBoyLoginService $loginService): JsonResponse
    {
        $rider = $this->findSellerRider($id, 'delivery_boy.manage');
        $loginService->rejectPasswordLogin($rider);

        return ApiResponseType::sendJsonResponse(true, 'Rider password login disapproved.', [
            'rider_id' => $rider->id,
            'login_approval_status' => $rider->fresh()->login_approval_status,
            'login_approved_until' => null,
        ]);
    }

    private function sellerForPermission(string $permission): Seller
    {
        $user = auth()->user();
        $seller = $user?->seller();
        abort_unless($seller, 404, 'Seller account not found.');

        abort_unless($this->sellerHasPermission($permission, $seller), 403, 'Permission denied.');

        return $seller;
    }

    private function sellerHasPermission(string $permission, Seller $seller): bool
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

    private function findSellerRider(int $id, string $permission): DeliveryBoy
    {
        $seller = $this->sellerForPermission($permission);

        return DeliveryBoy::query()
            ->with('user')
            ->where('seller_id', $seller->id)
            ->findOrFail($id);
    }

    private function riderData(DeliveryBoy $rider): array
    {
        return [
            'id' => $rider->id,
            'user_id' => $rider->user_id,
            'name' => $rider->user?->name,
            'email' => $rider->user?->email,
            'mobile' => $rider->user?->mobile,
            'delivery_zone_id' => $rider->delivery_zone_id,
            'status' => $rider->status,
            'verification_status' => $rider->verification_status,
            'login_approval_status' => $rider->login_approval_status,
            'login_approval_requested_at' => $rider->login_approval_requested_at,
            'login_approved_until' => $rider->login_approved_until,
            'qr_login_expires_at' => $rider->qr_login_expires_at,
        ];
    }

    private function riderPaginator(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->getCollection()->map(fn (DeliveryBoy $rider) => $this->riderData($rider)),
        ];
    }
}
