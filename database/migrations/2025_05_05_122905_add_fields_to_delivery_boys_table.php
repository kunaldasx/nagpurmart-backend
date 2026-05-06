<?php

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('user_id');
            $table->foreignId('delivery_zone_id')->nullable()->after('user_id')->constrained('delivery_zones');
            $table->text('address')->nullable()->after('full_name');
            $table->string('driver_license')->nullable()->after('address');
            $table->string('driver_license_number')->nullable()->after('driver_license');
            $table->string('vehicle_type')->nullable()->after('driver_license_number');
            $table->string('vehicle_registration')->nullable()->after('vehicle_type');
            $table->enum('verification_status', [
                DeliveryBoyVerificationStatusEnum::PENDING(),
                DeliveryBoyVerificationStatusEnum::REJECTED(),
                DeliveryBoyVerificationStatusEnum::VERIFIED()
            ])->default(DeliveryBoyVerificationStatusEnum::PENDING())->after('vehicle_registration');
            $table->text('verification_remark')->nullable()->after('verification_status');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropForeign(['delivery_zone_id']);
            $table->dropColumn([
                'full_name',
                'address',
                'driver_license',
                'driver_license_number',
                'vehicle_type',
                'vehicle_registration',
                'verification_status',
                'verification_remark',
                'delivery_zone_id'
            ]);
        });
    }
};
