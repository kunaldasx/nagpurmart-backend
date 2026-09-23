<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Http\Resources\DeliveryTimeSlotResource;
use App\Models\DeliveryTimeSlot;
use App\Services\CurrencyService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeliveryTimeSlotController extends Controller
{
    use ChecksPermissions, PanelAware;

    public function index(): View
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_VIEW());
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => 'ID'],
            ['data' => 'store', 'name' => 'store', 'title' => 'Store'],
            ['data' => 'day', 'name' => 'day', 'title' => 'Day'],
            ['data' => 'time', 'name' => 'time', 'title' => 'Time'],
            ['data' => 'max_orders', 'name' => 'max_orders', 'title' => 'Orders per day'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
        ];
        return view('admin.delivery-time-slots.index', compact('columns'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_CREATE());
        return $this->save($request);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_VIEW());
        $slot = DeliveryTimeSlot::with('store')->find($id);
        if (!$slot) return ApiResponseType::sendJsonResponse(false, 'Delivery slot not found.', [], 404);
        return ApiResponseType::sendJsonResponse(true, 'Delivery slot retrieved successfully.', new DeliveryTimeSlotResource($slot));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_EDIT());
        $slot = DeliveryTimeSlot::find($id);
        if (!$slot) return ApiResponseType::sendJsonResponse(false, 'Delivery slot not found.', [], 404);
        return $this->save($request, $slot);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_DELETE());
        $slot = DeliveryTimeSlot::find($id);
        if (!$slot) return ApiResponseType::sendJsonResponse(false, 'Delivery slot not found.', [], 404);
        DB::transaction(function () use ($slot) {
            $slot->orders()->update(['delivery_time_slot_id' => null]);
            $slot->delete();
        });
        return ApiResponseType::sendJsonResponse(true, 'Delivery slot deleted successfully.', []);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->checkPermission(AdminPermissionEnum::DELIVERY_SLOT_VIEW());
        $query = DeliveryTimeSlot::with('store');
        $search = $request->input('search.value');
        if ($search) {
            $query->whereHas('store', fn ($store) => $store->where('name', 'like', "%{$search}%"))
                ->orWhere('day_of_week', 'like', "%{$search}%");
        }
        $total = DeliveryTimeSlot::count();
        $filtered = (clone $query)->count();
        $rows = $query->orderBy('day_of_week')->orderBy('start_time')
            ->skip((int) $request->input('start', 0))->take((int) $request->input('length', 10))->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn (DeliveryTimeSlot $slot) => [
                'id' => $slot->id,
                'store' => e($slot->store?->name ?? 'All stores'),
                'day' => Str::headline($slot->day_of_week),
                'time' => e($slot->start_time . ' - ' . $slot->end_time),
                'max_orders' => $slot->max_orders,
                'status' => view('partials.status', ['status' => $slot->is_active ? 'active' : 'inactive'])->render(),
                'action' => view('partials.actions', ['modelName' => 'delivery-time-slot', 'id' => $slot->id, 'editPermission' => $this->hasPermission(AdminPermissionEnum::DELIVERY_SLOT_EDIT()), 'deletePermission' => $this->hasPermission(AdminPermissionEnum::DELIVERY_SLOT_DELETE()), 'title' => $slot->day_of_week . ' slot', 'mode' => 'model_view'])->render(),
            ]),
        ]);
    }

    private function save(Request $request, ?DeliveryTimeSlot $slot = null): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'day_of_week' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'max_orders' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $slot = $slot ?: new DeliveryTimeSlot();
        $slot->fill($validated);
        $slot->is_active = $request->boolean('is_active');
        $slot->save();
        return ApiResponseType::sendJsonResponse(true, $slot->wasRecentlyCreated ? 'Delivery slot created successfully.' : 'Delivery slot updated successfully.', new DeliveryTimeSlotResource($slot->load('store')), $slot->wasRecentlyCreated ? 201 : 200);
    }

    private function checkPermission(string $permission): void
    {
        abort_unless($this->hasPermission($permission), 403);
    }
}