# Delivery Zone API

## Estimate delivery time

```http
GET /api/delivery-zone/estimate?latitude=<lat>&longitude=<lng>
```

Optional parameters:

- `store_id`: use a specific store.
- `address_id`: use the authenticated user's saved address when coordinates are omitted.

The estimate formula is:

```text
5 minutes base preparation
+ delivery zone delay
+ (ceil(distance in km) * 3 minutes)
```

For example, with a 10-minute delay and a 1.2 km distance:

```text
5 + 10 + (ceil(1.2) * 3) = 18 minutes
```

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
        "distance_km": 1.2,
        "distance_minutes": 6,
        "base_prep_time_minutes": 5,
        "delay": 10,
        "comment": "Heavy rain",
        "calculation": {
            "base_prep_time_minutes": 5,
            "additional_delay_minutes": 10,
            "distance_minutes": 6,
            "estimated_time_minutes": 21
        },
        "estimated_time_minutes": 21
    }
}
```

The calculation above uses `ceil(1.2) * 3`, so the total is `5 + 10 + 6 = 21` minutes. If the distance is exactly 1 km, the total is `5 + 10 + 3 = 18` minutes.

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

## Admin fields

Delivery zones now support:

- `delay`: nonnegative integer minutes, default `0`.
- `comment`: optional explanation such as `Heavy rain` or `Rush hour`.
- `delivery_paused`: set to `true` to temporarily stop deliveries in the zone.
- `delivery_paused_until`: optional date/time for automatic reopening.
- `delivery_pause_comment`: optional customer-facing reason such as `Severe rain`.

These controls are available in the admin delivery-zone form under the temporary delivery pause section. Pausing a zone affects delivery availability checks and the estimate endpoint; it does not change the zone's permanent `status`.

Run the delivery-zone migration after deployment:

```bash
php artisan migrate
```
