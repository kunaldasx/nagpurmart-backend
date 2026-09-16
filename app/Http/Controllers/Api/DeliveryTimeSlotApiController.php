<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryTimeSlotResource;
use App\Models\DeliveryTimeSlot;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryTimeSlotApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'day_of_week' => ['nullable', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $date = $request->input('date');
        $day = $date ? strtolower(Carbon::parse($date)->format('l')) : strtolower((string) $request->input('day_of_week'));
        $slots = DeliveryTimeSlot::with('store')
            ->where('is_active', true)
            ->when($day, fn ($query) => $query->where('day_of_week', $day))
            ->when($request->filled('store_id'), fn ($query) => $query->where('store_id', $request->integer('store_id')))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->each(function (DeliveryTimeSlot $slot) use ($date) {
                $slot->booked_orders = $date
                    ? Order::where('delivery_time_slot_id', $slot->id)
                        ->whereDate('delivery_date', $date)
                        ->whereNotIn('status', ['cancelled', 'failed'])
                        ->count()
                    : 0;
                $slot->remaining_orders = max(0, (int) $slot->max_orders - (int) $slot->booked_orders);
            })
            ->filter(fn (DeliveryTimeSlot $slot) => (int) $slot->remaining_orders > 0)
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Delivery slots fetched successfully.',
            'data' => DeliveryTimeSlotResource::collection($slots),
        ]);
    }
}