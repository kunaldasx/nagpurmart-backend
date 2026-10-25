# Rider Order Popup API

This flow mirrors the seller popup pattern but applies to the rider.

## Incoming order push alerts

When a seller finishes preparing a bagged order, the backend reserves it to one random eligible rider and stores that rider ID in `orders.delivery_boy_id` before sending the alert. Eligible means active, a fresh location report (within 2 minutes), within 200 m of the pickup store, registered FCM token, and no pending offer or active delivery. A rider can hold only one pending offer at a time. The alert keeps ringing until a person accepts, cancels, or reassigns the order. Android receives a high-priority, data-only message with no `notification` block:

```json
{
    "data": {
        "type": "new_order",
        "order_id": "48213",
        "pickup": "Sharma Kirana",
        "drop": "Dharampeth",
        "distance_km": "3.2",
        "amount": "420"
    }
}
```

Only the rider whose ID matches `orders.delivery_boy_id` and holds the pending offer may see the order from `GET /api/delivery-boy/orders/available` or accept it. Another rider's offer or an order already accepted/reassigned returns HTTP `409` with a message that the order is no longer assigned to this rider. A rider becomes eligible for another order when their active delivery is completed and a fresh location report places them near a pickup store.

If the rider's alert should stop, the backend sends this data-only message to the same rider:

```json
{
    "data": {
        "type": "order_alert_cancel",
        "order_id": "48213",
        "reason": "reassigned"
    }
}
```

`reason` is `reassigned` or `cancelled`. No timer sends a stop alert, clears `delivery_boy_id`, or reassigns the order. Dispatch is retried when a bagged order becomes ready, a rider goes active, or an active rider reports a new location.

Rider location heartbeat:

```http
POST /api/delivery-boy/update-current-location
Authorization: Bearer <sanctum_token>
Content-Type: application/json
```

```json
{ "latitude": 21.1458, "longitude": 79.0882 }
```

The response includes the saved location and `recorded_at`. Active riders should keep this endpoint updated while on duty so the dispatcher can use fresh coordinates.

### Frontend location tracking requirements

The backend cannot read the device GPS itself. The rider app must request location permission, obtain coordinates from the device location provider, and call the endpoint above. Do not ask the rider to enter coordinates manually.

Use this duty lifecycle:

1. When the rider goes online, obtain a fresh device location and call `POST /api/delivery-boy/status/update` with `status: "active"`, `latitude`, and `longitude`. This records the initial location and updates the rider's delivery zone.
2. While the rider remains active, run a platform location subscription and send the latest coordinate to `POST /api/delivery-boy/update-current-location` at least once every 60 seconds. Also send sooner when the rider has moved at least 50 m. This keeps reports comfortably inside the backend's 2-minute freshness limit without continuously requesting high-accuracy GPS fixes.
3. Continue location updates while the app is backgrounded if the rider remains online. Use the platform's approved background-location mode/foreground service and user-visible permission disclosures. If background location is unavailable or denied, show that the rider may not receive nearby offers while the app is not reporting location.
4. When the rider goes offline, call `POST /api/delivery-boy/status/update` with `status: "inactive"`, then stop the location subscription. Stop tracking on logout as well.

The request body for each location heartbeat is:

```json
{
    "latitude": 21.1458,
    "longitude": 79.0882
}
```

The endpoint requires the rider's Sanctum bearer token and returns the saved location in `data`, including its `recorded_at` timestamp. Send coordinates as numeric latitude/longitude values and handle `401` by stopping tracking and requiring login again; handle validation errors without retrying the same invalid coordinates.

For battery and network efficiency, reuse one native location subscription while online rather than starting a new GPS request for every API call. Prefer balanced-power accuracy with an expected horizontal accuracy of 50 m or better near a pickup store. Coalesce movement updates, and on network failure retain only the newest coordinate: retry it with bounded backoff and immediately on connectivity restoration instead of uploading a backlog of stale points. Send an initial heartbeat immediately after activating duty and resume one immediately when the app returns to the foreground.

`active`/`inactive` remains an explicit duty status; the backend does not change it merely because GPS moves or becomes stale. The backend combines active status with verification, a registered FCM token, a location report no older than 2 minutes, distance within 200 m of the pickup store, and no pending offer/active delivery to decide whether the rider can receive the next order. Location updates from an active rider trigger dispatch evaluation, so keep the heartbeat running after an order is completed while the rider remains online.

The rider should only see orders that already have a valid seller-assigned bag and at least one item in `preparing` state. Once the rider accepts the order, they must scan the same assigned bag before the order items can move to `collected`.

## 1. Fetch available rider orders

```http
GET /api/delivery-boy/orders/available?order_mode=regular
Accept: application/json
Authorization: Bearer <sanctum_token>
```

The endpoint returns only orders reserved to the authenticated rider in their delivery zone where:

- `delivery_boy_id` equals the authenticated rider's ID
- a pending `delivery_boy_order_offers` row exists for that rider
- `sellerOrders` contains a bag
- at least one seller-order item is `preparing`

Response:

```json
{
    "success": true,
    "message": "Orders fetched successfully",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1,
        "from": 1,
        "to": 1,
        "orders": [
            {
                "id": 8801,
                "order_number": "NM-20260930-AB3XK9ZM",
                "order_mode": "regular",
                "status": "pending",
                "created_at": "2026-09-30T10:00:00.000000Z",
                "customer": {
                    "name": "Customer Name",
                    "phone": "+911234567890",
                    "address": "12 Example Road, Nagpur, Maharashtra, 440001"
                },
                "payment_method": "cod",
                "total": 499.0,
                "delivery_route": null,
                "items": [
                    {
                        "id": 9901,
                        "product_id": 440,
                        "product_name": "Example Product",
                        "variant_name": "500 g",
                        "status": "preparing",
                        "quantity": 2,
                        "subtotal": 499.0,
                        "store": {
                            "id": 10,
                            "name": "Store One",
                            "contact_number": "+919876543210"
                        }
                    }
                ],
                "seller_orders": [
                    {
                        "id": 701,
                        "seller": {
                            "id": 22,
                            "name": "Seller Name",
                            "phone": "+919999999999"
                        },
                        "bag": {
                            "id": 33,
                            "barcode": "BAG-000033",
                            "pickup_status": "assigned",
                            "picked_up_at": null,
                            "picked_up_by_delivery_boy_id": null
                        }
                    }
                ]
            }
        ]
    }
}
```

Frontend use:

- Show the popup only when at least one `seller_orders[].bag` object exists.
- Show the seller/store contact from `seller_orders[].seller` or `items[].store.contact_number`.
- Display the expected bag barcode so the rider knows which physical bag to scan.

## 2. Accept the order

```http
POST /api/delivery-boy/orders/8801/accept
Accept: application/json
Authorization: Bearer <sanctum_token>
```

No request body is required.

Success (`200`):

```json
{
    "success": true,
    "message": "Order accepted successfully",
    "data": {
        "order": {
            "id": 8801,
            "order_number": "NM-20260930-AB3XK9ZM",
            "status": "assigned",
            "delivery_boy_id": 14,
            "seller_orders": [
                {
                    "id": 701,
                    "bag": {
                        "id": 33,
                        "barcode": "BAG-000033",
                        "pickup_status": "assigned"
                    }
                }
            ]
        }
    }
}
```

If the order is already taken or not in the rider's delivery zone, the API returns `404` or `422` with a message indicating the order is unavailable.

## 3. Rider popup flow after acceptance

After accepting the order, the mobile app should present a rider popup with the following data:

- order number
- customer details
- store/seller contact details
- assigned bag barcode
- bag scan scanner field
- pickup action button

### UI logic

1. Rider sees the pickup popup.
2. Rider taps the scanner or enters the bag barcode.
3. App calls the bag-scan endpoint.
4. If the bag matches the assigned order and the rider is the assigned rider, the server marks the bag as scanned and updates all `preparing` items in that seller order to `collected`.
5. The rider is then allowed to proceed with the normal delivery flow.

## 4. Scan the assigned bag

```http
POST /api/delivery-boy/orders/8801/scan-bag
Content-Type: application/json
Accept: application/json
Authorization: Bearer <sanctum_token>
```

Request:

```json
{
    "barcode": "BAG-000033"
}
```

Validation rules:

- `barcode` is required
- barcode must match the assigned bag for this order
- the bag must belong to the same order
- the rider must be the one assigned to the order

Success (`200`):

```json
{
    "success": true,
    "message": "Assigned bag scanned and its prepared items were picked up.",
    "data": {
        "order_id": 8801,
        "order_status": "assigned",
        "bag": {
            "id": 33,
            "seller_order_id": 701,
            "barcode": "BAG-000033",
            "pickup_status": "picked_up",
            "picked_up_at": "2026-10-01T10:15:00.000000Z"
        },
        "collected_order_item_ids": [9901]
    }
}
```

Rejected scenarios:

### Wrong order / wrong bag

```json
{
    "success": false,
    "message": "This bag is not assigned to the scanned order.",
    "data": {
        "bag_barcode": "BAG-999999"
    }
}
```

HTTP status: `422`

### Bag already scanned by another rider

```json
{
    "success": false,
    "message": "This bag has already been picked up by another rider.",
    "data": {}
}
```

HTTP status: `422`

### Bag has no preparing items left

```json
{
    "success": false,
    "message": "This bag has no preparing items available for pickup.",
    "data": {}
}
```

HTTP status: `422`

## 5. Update item status manually after scan

Once the bag has been successfully scanned, the app may use the normal item-status update endpoint.

```http
PUT /api/delivery-boy/order-items/9901/status
Content-Type: application/json
Accept: application/json
Authorization: Bearer <sanctum_token>
```

Request:

```json
{
    "status": "collected"
}
```

The server enforces the bag-scan gate:

- the item must belong to the rider's assigned order
- the related seller order bag must have `picked_up_by_delivery_boy_id = current rider id`
- `picked_up_at` must be set

If the bag scan was not completed first, the request fails:

```json
{
    "success": false,
    "message": "Scan the bag assigned to this seller order before confirming pickup.",
    "data": {
        "order_item_id": 9901,
        "bag_scan_required": true
    }
}
```

HTTP status: `422`

For COD orders, fetch payment instructions and collect payment before setting an item to `delivered`. Razorpay UPI QR payments must reach `completed` through backend confirmation; include `payment_collection.method: "cash"` only for cash. See [DELIVERY_BOY_COD_PAYMENT_API.md](DELIVERY_BOY_COD_PAYMENT_API.md) for the rider app flow and complete request and response structures.

## 6. Get order detail after pickup

```http
GET /api/delivery-boy/orders/8801
Accept: application/json
Authorization: Bearer <sanctum_token>
```

Response includes the same seller-order and bag metadata so the frontend can render the popup state accurately when the user reopens the order:

```json
{
    "success": true,
    "message": "Order fetched successfully",
    "data": {
        "order": {
            "id": 8801,
            "order_number": "NM-20260930-AB3XK9ZM",
            "status": "assigned",
            "seller_orders": [
                {
                    "id": 701,
                    "seller": {
                        "id": 22,
                        "name": "Seller Name",
                        "phone": "+919999999999"
                    },
                    "bag": {
                        "id": 33,
                        "barcode": "BAG-000033",
                        "pickup_status": "picked_up",
                        "picked_up_at": "2026-10-01T10:15:00.000000Z",
                        "picked_up_by_delivery_boy_id": 14
                    }
                }
            ]
        }
    }
}
```

## 7. Recommended frontend flow

```text
fetch available orders
  -> rider selects an order
  -> accept order
  -> show rider popup
  -> scan assigned bag barcode
  -> backend validates same order + same rider + same bag
  -> backend marks all preparing items as collected
  -> show success UI and proceed to delivery
```

Important rules for frontend:

- Do not allow the rider to mark an item as `collected` before the bag scan completes.
- Use the assigned bag barcode from the API payload, not a freeform bag value.
- Re-check the returned `pickup_status` and `picked_up_at` values to avoid duplicate scans.
- Persist the bag barcode in the popup state until the order is closed or delivered.

## 8. Database / schema assumptions

The bag scan and pickup state is stored on the `bags` table:

```sql
picked_up_by_delivery_boy_id BIGINT NULL
picked_up_at TIMESTAMP NULL
```

This lets the backend verify:

- the bag belongs to the seller order of the accepted order
- the same rider scanned it
- pickup is completed before `collected` is allowed

This is the key business rule that prevents a rider from bypassing the seller dispatch flow.
