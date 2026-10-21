# Rider Order Popup API

This flow mirrors the seller popup pattern but applies to the rider.

## Incoming order push alerts

When a seller finishes preparing a bagged order, the backend creates a time-limited offer for one active rider in the order's delivery zone. The rider must have a registered FCM token. Android receives a high-priority, data-only message with no `notification` block:

```json
{
    "data": {
        "type": "new_order",
        "order_id": "48213",
        "expires_at": "2026-10-20T10:43:30Z",
        "pickup": "Sharma Kirana",
        "drop": "Dharampeth",
        "distance_km": "3.2",
        "amount": "420"
    }
}
```

Only the rider holding the unexpired offer may see the order from `GET /api/delivery-boy/orders/available` or accept it. A stale offer, another rider's offer, or an already-claimed order returns HTTP `409` with a message that the order is no longer assigned to this rider.

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

`reason` is `reassigned`, `cancelled`, or `expired`. The default offer timeout is 30 seconds and can be changed with `DELIVERY_BOY_ORDER_OFFER_TIMEOUT_SECONDS`. On expiry, the backend sends the stop message and offers the order to the next eligible active rider. If no eligible active rider with an FCM token remains, it writes a Laravel alert log entry for dispatcher attention.

Run a Laravel queue worker for delayed expiry jobs to fire at the configured timeout.

The rider should only see orders that already have a valid seller-assigned bag and at least one item in `preparing` state. Once the rider accepts the order, they must scan the same assigned bag before the order items can move to `collected`.

## 1. Fetch available rider orders

```http
GET /api/delivery-boy/orders/available?order_mode=regular
Accept: application/json
Authorization: Bearer <sanctum_token>
```

The endpoint returns only orders in the rider's delivery zone where:

- `delivery_boy_id` is null
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
