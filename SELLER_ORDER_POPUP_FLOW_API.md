# Seller Order Popup Flow

This document is the frontend contract for seller web and mobile apps. The backend creates a durable offer for one eligible seller user per seller order. It does not return the shared seller-wide incoming-order queue to every user.

## Assignment behavior

- An incoming seller order is offered to one user who has `order.popup` permission (or is the default seller owner), is active, has a fresh location, and is within 200 m of at least one store in that seller order.
- A system user must also have an active seller membership and a currently approved login.
- A location is fresh for two minutes. While the user is active, send a location heartbeat at least once every 60 seconds.
- Each user can hold one pending or accepted popup offer at a time. Additional orders stay unassigned until the user dispatches the current order.
- After `verify-and-prepare` succeeds, that offer is completed and the backend immediately tries to assign the next eligible order to that user.
- Offers are committed before push delivery. Push failure is logged; the assigned offer remains available from the pending endpoint.
- Wholesale orders are offered only during the existing popup window: the delivery-slot end time is now through 30 minutes from now.
- The seller owner and an authorized system user both use their own authenticated user ID as the offer recipient.
- Orders without an eligible nearby user remain unassigned. New heartbeats, pending-order polling, and new-order events retry assignment.

The radius check uses coordinates reported by the app/browser. The frontend must request device/browser location permission and send GPS coordinates; do not use a typed address or customer coordinates.

## Authentication and base URLs

### Seller mobile app

All `/api/seller/...` endpoints below require:

```http
Authorization: Bearer <sanctum_access_token>
Accept: application/json
Content-Type: application/json
```

The authenticated user must belong to the seller. The API enforces `order.popup` (the default seller role is allowed without the explicit permission).

### Seller web panel

The web panel uses its existing authenticated session and CSRF protection. The equivalent routes are:

```text
GET  /seller/orders/pending-regular
POST /seller/orders/popup-presence
POST /seller/orders/{seller_order_id}/accept-items
POST /seller/orders/{seller_order_id}/assign-bag
POST /seller/orders/{seller_order_id}/verify-and-prepare
```

The web response for the pending endpoint is the legacy shape described in the web section below. The seller layout reports location automatically while the popup is enabled.

## 1. Register push token and start popup presence

The seller app should register an FCM token during seller login (the login endpoint accepts `fcm_token` and `device_type`). The app must keep the token current using Firebase's token-refresh callback and the existing seller login/token registration flow.

When the user opens the order popup feature and grants location access, start a background-capable location subscription and call:

```http
POST /api/seller/orders/popup-presence
```

Request while active:

```json
{
    "active": true,
    "latitude": 21.1458,
    "longitude": 79.0882
}
```

Success (`200`):

```json
{
    "success": true,
    "message": "Seller popup presence updated.",
    "data": {
        "active": true,
        "latitude": "21.14580000",
        "longitude": "79.08820000",
        "last_seen_at": "2026-10-26T04:53:00+00:00"
    }
}
```

Send fresh coordinates immediately on activation, at least every 60 seconds while the user remains active, and after a meaningful location change. Use a platform location subscription; do not start a high-accuracy GPS request for every API request. Keep permitted background location updates running when the seller app is backgrounded so the server can continue to assign nearby orders.

On logout, when the user disables order alerts, or when location permission is revoked, send:

```json
{ "active": false }
```

Validation failure returns `422`; latitude must be between -90 and 90 and longitude between -180 and 180. An unauthenticated request returns `401`; a user without popup permission returns `403`. The backend also expires presence automatically after two minutes without a heartbeat.

The seller web panel calls the equivalent session-authenticated endpoint while the browser grants location permission. If location is unavailable or denied, show a clear status to the user; they will not receive new nearby offers.

## 2. Handle high-priority Firebase order notification

When an offer is reserved, the backend sends a visible high-priority FCM alert to the assigned user's registered tokens. Android receives high-priority delivery with the `seller_orders` notification channel and default sound; Apple devices receive an APNs alert at priority 10.

An FCM token is not required for a foreground web/session user to receive an assigned offer through polling. If no token is registered, the offer remains assigned and available from the pending endpoint, but a closed app cannot receive a push; the backend logs this condition.

Example FCM payload (illustrative):

```json
{
    "notification": {
        "title": "New order received",
        "body": "Order #NM-20261026-AB3XK9ZM is waiting for your review."
    },
    "data": {
        "type": "seller_new_order",
        "seller_order_id": "701",
        "order_id": "8801",
        "order_number": "NM-20261026-AB3XK9ZM",
        "order_mode": "regular"
    },
    "android": {
        "priority": "high",
        "notification": {
            "channel_id": "seller_orders",
            "priority": "max",
            "sound": "default"
        }
    },
    "apns": {
        "headers": {
            "apns-priority": "10",
            "apns-push-type": "alert"
        }
    }
}
```

The notification is a wake-up/navigation hint, not the order record. On receipt or tap, open the seller order popup and fetch the assigned order from the pending endpoint. Do not accept or dispatch based only on notification data. In the foreground, the app may show its in-app alert and refresh the same endpoint.

For Android, create the `seller_orders` notification channel with high importance. For background/terminated app presentation, ensure the app's notification permission is granted. On iOS, request notification permission and configure remote notifications/background modes according to Apple requirements. Firebase/OS delivery is best-effort; keep the pending endpoint as the source of truth.

## 3. Fetch the current user's assigned incoming orders

```http
GET /api/seller/orders/pending-regular?order_mode=regular
```

Use `order_mode=wholesale` for wholesale orders. Add `popup=1` to apply the 30-minute wholesale popup window. The API returns only orders assigned to the authenticated user with a pending or accepted offer. Poll while the popup feature is active (for example every 5 seconds in the foreground), and refresh when a push arrives or the app resumes.

API success (`200`):

```json
{
    "success": true,
    "message": "Pending orders fetched successfully",
    "data": {
        "orders": [
            {
                "seller_order_id": 701,
                "order_id": 8801,
                "order_number": "NM-20261026-AB3XK9ZM",
                "order_mode": "regular",
                "created_at": "2026-10-26T04:50:00.000000Z",
                "customer": {
                    "name": "Customer Name",
                    "phone": "+911234567890",
                    "address": "12 Example Road, Nagpur, Maharashtra, 440001"
                },
                "payment_method": "cod",
                "total": 499.0,
                "bag": null,
                "items": [
                    {
                        "order_item_id": 9901,
                        "product_id": 440,
                        "product": "Example Product",
                        "variant": "500 g",
                        "barcode": "8901234500021",
                        "sku": "EXAMPLE-500G",
                        "variant_weight": 0.5,
                        "variant_dimensions": "12 × 8 × 6 cm",
                        "quantity": 2,
                        "status": "awaiting_store_response",
                        "subtotal": 499.0
                    }
                ]
            }
        ],
        "count": 1,
        "order_mode": "regular"
    }
}
```

`status` is `awaiting_store_response` for a new decision or `accepted` when resuming item verification. The expected barcode is supplied for internal comparison; never display it to the person scanning the item. `bag` is `null` until a bag is assigned.

When no order is currently assigned, `orders` is an empty array and `count` is `0`; this is a successful response, not an error. The web seller-panel equivalent returns:

```json
{
    "data": [],
    "count": 0,
    "order_mode": "regular"
}
```

## 4. Accept all items in the seller order

Accept only the `seller_order_id` returned to the current user:

```http
POST /api/seller/orders/701/accept-items
```

No body is required. Success (`200`):

```json
{
    "success": true,
    "message": "Seller order items accepted. Verify each item before preparing.",
    "data": {
        "seller_order_id": 701,
        "accepted_order_item_ids": [9901]
    }
}
```

The action is idempotent for already-accepted items. The backend records the offer as accepted but continues reserving the user until dispatch completes.

## 5. Verify every item in the popup

This check is local to the popup; it does not call an endpoint. Ask the user to scan/enter the barcode and confirm the ordered quantity for every item. Compare the scanned barcode with `items[].barcode` internally, but never display the expected barcode. Do not allow dispatch until all item checks pass.

## 6. Scan and assign a bag

After item checks pass, assign an available bag from this seller's inventory:

```http
POST /api/seller/orders/701/assign-bag
```

Request:

```json
{ "barcode": "BAG-000033" }
```

Success (`200`):

```json
{
    "success": true,
    "message": "Bag assigned to order successfully.",
    "data": {
        "seller_order_id": 701,
        "bag": {
            "id": 33,
            "barcode": "BAG-000033",
            "assigned_at": "2026-10-26T05:00:00.000000Z"
        }
    }
}
```

Unknown or unavailable bags return `422`. A bag already assigned to this order is accepted idempotently. Once assigned, the popup should show **Dispatch order**.

## 7. Dispatch the order

Submit each accepted item exactly once. `quantity` must equal the ordered quantity and `barcode` must exactly match the barcode stored on the ordered product variant. The server checks the full submitted set again and requires the assigned bag.

```http
POST /api/seller/orders/701/verify-and-prepare
```

Request:

```json
{
    "items": [
        {
            "order_item_id": 9901,
            "barcode": "8901234500021",
            "quantity": 2
        }
    ]
}
```

Success (`200`):

```json
{
    "success": true,
    "message": "All item checks passed and the order is now preparing.",
    "data": {
        "seller_order_id": 701,
        "bag": {
            "id": 33,
            "barcode": "BAG-000033"
        },
        "items": [
            {
                "order_item_id": 9901,
                "status": "preparing",
                "barcode": "8901234500021",
                "quantity": 2
            }
        ]
    }
}
```

On success, the seller popup offer is completed. The backend releases that user's active slot and immediately assigns the next eligible queued order if one is available nearby.

Mismatch or incomplete item set returns `422`:

```json
{
    "success": false,
    "message": "One or more item checks did not match the order.",
    "data": {
        "errors": [
            {
                "order_item_id": 9901,
                "field": "quantity",
                "expected": 2,
                "message": "Quantity does not match the ordered quantity."
            }
        ]
    }
}
```

Request-shape validation errors also return `422`. Missing bag returns `422` with `data.bag_required: true`. If an offer was assigned to another user, the accept/dispatch action returns `409`; refresh the pending endpoint and do not retry that seller order. Unauthenticated requests return `401`; an order not owned by the seller returns `404`; insufficient permission returns `403`.

## Frontend implementation checklist

1. Register and refresh the device's FCM token during authenticated seller app sessions.
2. Check popup permission (`order.popup`) or the default seller-owner role before rendering the popup.
3. Request device location permission, activate popup presence, and heartbeat every 60 seconds while active, including in the background where the platform permits.
4. Handle `seller_new_order` notifications by opening the popup and refreshing the authenticated pending endpoint; do not trust push data as the order source.
5. Display only the order returned to the authenticated user. Another system user will not receive the same active seller order.
6. Accept, verify each item, assign a bag, and dispatch in that order.
7. Keep sending location heartbeats until the user disables popup availability or logs out. Stop tracking and send `active: false` on those transitions.
8. After a successful dispatch, refresh immediately; the same user may be assigned the next nearby queued order.
