<?php

namespace Database\Seeders;

use App\Enums\Subscription\SubscriptionDurationTypeEnum;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanLimit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultSubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder is for the system that doesn't want to use the subscription plan feature
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Ensure there is a single default plan with unlimited duration
            $plan = SubscriptionPlan::query()
                ->where('is_default', true)
                ->first();

            if (!$plan) {
                // Create the default plan
                $plan = SubscriptionPlan::create([
                    'name' => 'Default Plan',
                    'description' => 'System default subscription plan with unlimited duration and unlimited usage limits.',
                    'price' => 0,
                    'duration_type' => SubscriptionDurationTypeEnum::UNLIMITED(),
                    'duration_days' => null,
                    'is_free' => true,
                    'is_default' => true,
                    'is_recommended' => false,
                    'status' => true,
                ]);

                // Make sure no other plan is marked as default
                SubscriptionPlan::query()
                    ->where('id', '!=', $plan->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            } else {
                // Normalize attributes in case they drifted
                $plan->fill([
                    'price' => 0,
                    'duration_type' => SubscriptionDurationTypeEnum::UNLIMITED(),
                    'duration_days' => null,
                    'is_free' => true,
                    'is_default' => true,
                    'status' => true,
                ])->save();
            }

            foreach (SubscriptionPlanKeyEnum::values() as $key) {
                SubscriptionPlanLimit::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'key' => $key,
                    ],
                    [
                        // null value denotes unlimited usage as per UI expectations
                        'value' => null,
                    ]
                );
            }
        });
    }
}
