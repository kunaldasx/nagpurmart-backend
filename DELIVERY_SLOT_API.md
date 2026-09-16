# Delivery Slots API

## Get available slots

```http
GET /api/delivery-slots?date=2026-09-18&store_id=12
```

`date` is optional but recommended for checkout. The endpoint returns active slots for that weekday and excludes slots whose `remaining_orders` is zero. `store_id` is optional. You can use `day_of_week=monday` instead of `date` when showing a weekly schedule.

```json
{
    "success": true,
    "message": "Delivery slots fetched successfully.",
    "data": [
        {
            "id": 7,
            "store_id": 12,
            "store": { "id": 12, "name": "Main Store" },
            "day_of_week": "friday",
            "date": "2026-09-18",
            "start_time": "09:00",
            "end_time": "11:00",
            "label": "09:00 - 11:00",
            "max_orders": 20,
            "booked_orders": 4,
            "remaining_orders": 16,
            "is_active": true
        }
    ]
}
```

## Wholesale checkout

When `order_mode=wholesale`, submit the selected slot and date to the existing order endpoint:

```http
POST /api/orders
```

```json
{
    "order_mode": "wholesale",
    "delivery_time_slot_id": 7,
    "delivery_date": "2026-09-18"
}
```

The order response includes `delivery_date`, `delivery_time_slot_id`, and `delivery_time_slot` with the selected time. Regular orders may omit these fields.
