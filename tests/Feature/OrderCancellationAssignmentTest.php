<?php

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Services\DeliveryBoyOrderOfferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('orders', function ($table): void {
        $table->id();
        $table->string('status');
        $table->timestamps();
    });

    Schema::create('delivery_boy_assignments', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('assignment_type');
        $table->string('status');
        $table->timestamps();
    });
});

it('releases active delivery assignments for canceled orders and reconciles existing stale rows', function () {
    $cancelledOrder = Order::query()->create(['status' => OrderStatusEnum::READY_FOR_PICKUP()]);
    $otherOrder = Order::query()->create(['status' => OrderStatusEnum::READY_FOR_PICKUP()]);
    $staleOrder = Order::query()->create(['status' => OrderStatusEnum::CANCELLED()]);

    DB::table('delivery_boy_assignments')->insert([
        ['order_id' => $cancelledOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED()],
        ['order_id' => $cancelledOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::IN_PROGRESS()],
        ['order_id' => $cancelledOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::COMPLETED()],
        ['order_id' => $cancelledOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::RETURN_PICKUP(), 'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED()],
        ['order_id' => $otherOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED()],
        ['order_id' => $staleOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED()],
        ['order_id' => $staleOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::DELIVERY(), 'status' => DeliveryBoyAssignmentStatusEnum::IN_PROGRESS()],
        ['order_id' => $staleOrder->id, 'assignment_type' => DeliveryBoyAssignmentTypeEnum::RETURN_PICKUP(), 'status' => DeliveryBoyAssignmentStatusEnum::ASSIGNED()],
    ]);

    $offerService = Mockery::mock(DeliveryBoyOrderOfferService::class);
    $offerService->shouldReceive('cancelPendingForOrder')
        ->once()
        ->with($cancelledOrder->id, 'cancelled');
    $this->app->instance(DeliveryBoyOrderOfferService::class, $offerService);

    DB::transaction(function () use ($cancelledOrder): void {
        $cancelledOrder->update(['status' => OrderStatusEnum::CANCELLED()]);
    });

    expect(DB::table('delivery_boy_assignments')->orderBy('id')->pluck('status')->all())->toBe([
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::COMPLETED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
        DeliveryBoyAssignmentStatusEnum::IN_PROGRESS(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
    ]);

    $migration = require base_path('database/migrations/2026_10_25_000001_reconcile_cancelled_delivery_assignments.php');
    $migration->up();

    expect(DB::table('delivery_boy_assignments')->orderBy('id')->pluck('status')->all())->toBe([
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::COMPLETED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::CANCELED(),
        DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
    ]);
});
