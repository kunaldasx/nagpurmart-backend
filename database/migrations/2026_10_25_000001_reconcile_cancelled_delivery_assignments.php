<?php

use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('delivery_boy_assignments')
            ->where('assignment_type', DeliveryBoyAssignmentTypeEnum::DELIVERY())
            ->whereIn('status', [
                DeliveryBoyAssignmentStatusEnum::ASSIGNED(),
                DeliveryBoyAssignmentStatusEnum::IN_PROGRESS(),
            ])
            ->whereIn('order_id', function ($query): void {
                $query->select('id')
                    ->from('orders')
                    ->where('status', OrderStatusEnum::CANCELLED());
            })
            ->update(['status' => DeliveryBoyAssignmentStatusEnum::CANCELED()]);
    }

    public function down(): void
    {
        // Assignment statuses cannot be safely restored after this reconciliation.
    }
};
