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
ceil(delivery_wait_minutes
+ 5 minutes base preparation
+ distance_km * delivery_time_per_km
+ buffer_time)
```

`delivery_wait_minutes` is the time from now until delivery can next start: it is zero while the zone is open, or includes the wait until the next configured opening and/or a scheduled pause end. The ETA is measured from the time of the API request, so do not add this wait to `estimated_time_minutes` again in the frontend. When an admin pause has no configured end, its duration cannot be known; in that case the returned ETA cannot account for the unknown pause duration. `delivery_time_per_km` and `buffer_time` are configured on the delivery zone. Rush and regular orders use the same speed setting; rush delivery can still have a separate delivery charge. The legacy `delay` and `rush_delivery_time_per_km` database columns are no longer used for ETA calculations.

For a one-store route, distance is round-trip. For multiple stores, distance follows the same nearest-neighbor multi-store route used by checkout. To make a pre-checkout estimate match the cart/order ETA exactly, send the cart's store IDs:

```http
GET /api/delivery-zone/estimate?latitude=23.1168454&longitude=70.0280567&store_ids[]=12&store_ids[]=15
```

If `store_ids[]` is omitted, the endpoint selects the nearest store that serves the location and returns its single-store route estimate; that cannot predict a later multi-store cart route until the cart's store IDs are known. Store selection and delivery-zone matching are based on geographic coverage, not whether deliveries are currently within active hours.

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
        "active_hours": {
            "wednesday": { "start": "09:00", "end": "18:00" }
        },
        "delivery_paused": false,
        "delivery_pause_until": null,
        "delivery_pause_comment": null,
        "delivery_start_at": "2026-10-07T09:00:00.000000Z",
        "delivery_wait_minutes": 660,
        "calculation": {
            "base_prep_time_minutes": 5,
            "delivery_time_per_km": 5,
            "buffer_time_minutes": 4,
            "distance_minutes": 12,
            "delivery_wait_minutes": 660,
            "estimated_time_minutes": 681
        },
        "estimated_time_minutes": 681
    }
}
```

In this example, delivery can start in 660 minutes (11 hours), and the full ETA is `ceil(660 + 5 + (2.4 * 5) + 4) = 681` minutes. Checkout's payment summary and created order use the same schedule wait, calculation, and route distance.

### Frontend behavior

- Treat `is_deliverable` as the geographic/store coverage result. A zone being outside active hours or temporarily paused does not make it non-deliverable.
- When `is_deliverable` is `true`, continue showing products and allow adding to cart and placing an order even when `delivery_paused` is `true` or `delivery_wait_minutes` is greater than zero.
- Use `estimated_time_minutes` as the complete ETA from now, including the schedule/pause wait, preparation, travel, and buffer. Do not add `delivery_wait_minutes` a second time.
- Use `delivery_start_at` to show the next scheduled delivery start, formatted in the customer's local timezone. For example, show “Delivery starts tomorrow at 9:00 AM; estimated delivery in about 11 hours 21 minutes.” For an indefinite pause, don't present this schedule time as a confirmed pause-resume time.
- Minimal UI change for the existing ETA header: keep the current ETA text and layout, and populate the existing small status chip (currently shown above the ETA, e.g. “Heavy Rain”) from `delivery_pause_comment` when `delivery_paused` is `true` and the comment is non-empty. This makes the admin's pause reason visible without adding a new panel or changing the ETA presentation. Do not substitute `buffer_comment`; that describes general extra delivery time, not the active pause reason.
- Hide the pause-reason chip when `delivery_paused` is `false` or `delivery_pause_comment` is empty/null so an old reason is not shown after delivery resumes. If delivery is paused but there is no comment, keep the existing UI structure and optionally show a generic “Deliveries paused” label in that same chip.
- `delivery_pause_until` is the configured pause end and may be null. Keep using `estimated_time_minutes` for the ETA display and `delivery_start_at` for the next scheduled delivery start; the pause-reason chip is status context, not an additional time value.
- `active_hours` is a weekday-to-opening/closing map. A missing weekday means there are no custom hours for that day, so it remains active all day. It may be null when the zone has no schedule.
- Send all current cart `store_ids[]` when requesting an estimate for a cart, then refresh the estimate when the address or cart stores change.

When delivery is not geographically available or no selected/eligible store serves the location, the endpoint returns `is_deliverable: false` and does not include an ETA. The frontend may then show the location as unsupported and prevent checkout. For example, a covered location with a temporary pause remains deliverable and returns a successful ETA response; it is not represented by this unavailable response:

```json
{
    "success": true,
    "message": "labels.delivery_not_available",
    "data": {
        "is_deliverable": false,
        "coordinates": {
            "latitude": 23.1168454,
            "longitude": 70.0280567
        }
    }
}
```

The unavailable response may include `delivery_paused`, `delivery_pause_until`, and `delivery_pause_comment` when a matching paused zone exists, but these fields are informational and do not by themselves determine whether the customer can order. When an admin pause has no configured end time, the pause's eventual end is unknown to the API; show the pause status/comment without inventing a resume time, and treat the calculated ETA as schedule-based rather than a confirmed arrival estimate.

## Admin settings

The delivery-zone admin form has one ETA speed field and one buffer field:

- `delivery_time_per_km`: required nonnegative minutes per kilometer; used for regular and rush ETA.
- `buffer_time`: required nonnegative minutes added after prep and travel.
- `buffer_comment`: explanation for the buffer, e.g. `Allow extra time during heavy rain`.
- `delivery_paused`: set to `true` to mark deliveries as temporarily paused in the zone. This does not remove products or prevent orders; the estimate includes the known pause wait.
- `delivery_paused_until`: optional date/time for automatic reopening.
- `delivery_pause_comment`: customer-facing reason such as `Severe rain`.

The additional delivery delay and rush delivery time per kilometer inputs have been removed. Existing database columns are retained for backward compatibility but are ignored by ETA calculations. Active hours and pause status affect the estimated start time, not geographic delivery eligibility; these settings do not change the zone's permanent `status`.

Run the delivery-zone migration after deployment:

```bash
php artisan migrate
```
