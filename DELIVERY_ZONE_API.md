# Delivery Zone API

## Estimate delivery time

```http
GET /api/delivery-zone/estimate?latitude=<lat>&longitude=<lng>
```

Optional parameters:

- `store_id`: estimate for one specific store.
- `store_ids[]`: pass every store in the current cart to calculate the same multi-store route distance used by checkout. This takes precedence over `store_id`.
- `address_id`: use the authenticated user's saved address when coordinates are omitted.

The estimate and checkout/order APIs use the same ETA formula and route-distance helper:

```text
ceil(5 minutes base preparation
+ distance_km * delivery_time_per_km
+ buffer_time)
```

`delivery_time_per_km` and `buffer_time` are configured on the delivery zone. Rush and regular orders use the same speed setting; rush delivery can still have a separate delivery charge. The legacy `delay` and `rush_delivery_time_per_km` database columns are no longer used for ETA calculations.

For a one-store route, distance is round-trip. For multiple stores, distance follows the same nearest-neighbor multi-store route used by checkout. To make a pre-checkout estimate match the cart/order ETA exactly, send the cart's store IDs:

```http
GET /api/delivery-zone/estimate?latitude=23.1168454&longitude=70.0280567&store_ids[]=12&store_ids[]=15
```

If `store_ids[]` is omitted, the endpoint selects the nearest available store and returns its single-store route estimate; that cannot predict a later multi-store cart route until the cart's store IDs are known.

Successful response:

```json
{
    "success": true,
    "message": "labels.estimated_delivery_time",
    "data": {
        "is_deliverable": true,
        "store_id": 12,
        "store_name": "Downtown Store",
        "coordinates": {
            "latitude": 23.1168454,
            "longitude": 70.0280567
        },
        "store_ids": [12],
        "distance_km": 2.4,
        "distance_minutes": 12,
        "base_prep_time_minutes": 5,
        "delivery_time_per_km": 5,
        "buffer_time_minutes": 4,
        "buffer_comment": "Allow extra time during heavy rain",
        "calculation": {
            "base_prep_time_minutes": 5,
            "delivery_time_per_km": 5,
            "buffer_time_minutes": 4,
            "distance_minutes": 12,
            "estimated_time_minutes": 21
        },
        "estimated_time_minutes": 21
    }
}
```

The example is `ceil(5 + (2.4 * 5) + 4) = 21` minutes. Checkout's payment summary and created order use this same calculation and route distance.

When delivery is unavailable, the endpoint returns `is_deliverable: false` and does not include an estimate.

When the coordinates fall inside a zone that is temporarily paused, the unavailable response also identifies the pause:

```json
{
    "success": true,
    "message": "labels.delivery_not_available",
    "data": {
        "is_deliverable": false,
        "delivery_paused": true,
        "delivery_pause_until": "2026-09-20T18:00:00.000000Z",
        "delivery_pause_comment": "Severe rain",
        "coordinates": {
            "latitude": 23.1168454,
            "longitude": 70.0280567
        }
    }
}
```

If `delivery_pause_until` is set, delivery automatically becomes available after that time. If it is empty, delivery remains paused until an admin disables the pause. A normal out-of-zone response has `delivery_paused: false` and no pause details.

## Admin settings

The delivery-zone admin form has one ETA speed field and one buffer field:

- `delivery_time_per_km`: required nonnegative minutes per kilometer; used for regular and rush ETA.
- `buffer_time`: required nonnegative minutes added after prep and travel.
- `buffer_comment`: optional explanation for the buffer, e.g. `Allow extra time during heavy rain`.
- `delivery_paused`: set to `true` to temporarily stop deliveries in the zone.
- `delivery_paused_until`: optional date/time for automatic reopening.
- `delivery_pause_comment`: optional customer-facing reason such as `Severe rain`.

The additional delivery delay and rush delivery time per kilometer inputs have been removed. Existing database columns are retained for backward compatibility but are ignored by ETA calculations. Pausing a zone affects delivery availability checks and the estimate endpoint; it does not change the zone's permanent `status`.

Run the delivery-zone migration after deployment:

```bash
php artisan migrate
```
