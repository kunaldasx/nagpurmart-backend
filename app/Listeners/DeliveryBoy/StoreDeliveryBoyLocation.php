<?php

namespace App\Listeners\DeliveryBoy;

use App\Events\DeliveryBoy\DeliveryBoyStatusUpdatedEvent;
use App\Models\DeliveryBoyLocation;

class StoreDeliveryBoyLocation
{

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // You can inject any dependencies here if needed
    }

    /**
     * Handle the event.
     */
    public function handle(DeliveryBoyStatusUpdatedEvent $event): void
    {
        // Get the delivery boy from the event
        $deliveryBoy = $event->deliveryBoy;

        // Only store location if latitude and longitude are provided
        if ($event->latitude !== null && $event->longitude !== null) {
            // Create a new delivery boy location record
            DeliveryBoyLocation::updateOrCreate(
                ['delivery_boy_id' => $deliveryBoy->id],
                [
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'recorded_at' => now(),
                ]
            );
        }
    }
}
