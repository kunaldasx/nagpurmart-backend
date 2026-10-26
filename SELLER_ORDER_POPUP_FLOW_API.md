# Seller Order Popup Flow

This document is the frontend contract for seller web and mobile apps. The backend creates a durable offer for one eligible seller user per seller order. It does not return the shared seller-wide incoming-order queue to every user.

## Assignment behavior

- An incoming seller order is offered to one eligible system user first. A system user must have `order.popup` permission, an active seller membership, a current login approval, an active account/presence, a fresh location, and be within 200 m of a store in the seller order.
- The main seller owner is a fallback recipient only when no eligible system user is within 200 m of that order's store(s). The owner must have active, fresh popup presence and must not already have an active popup offer; the owner's location is not required or checked.
- A system user must also have an active seller membership and a currently approved login.
- A location is considered fresh for two minutes. A stale presence is excluded from new assignment; the presence row itself is not automatically deactivated.
- Each user can hold one pending or accepted popup offer at a time. Additional orders stay unassigned until the user dispatches the current order.
- After `verify-and-prepare` succeeds, that offer is completed and the backend immediately tries to assign the next eligible order to that user.
- The one-order-at-a-time limit is shared across regular and wholesale orders.
- Offers are committed before push delivery. Push failure is logged; the assigned offer remains available from the pending endpoint.
- Wholesale orders are offered only during the existing popup window: the delivery-slot end time is now through 30 minutes from now.
- A nearby eligible system user always takes priority over the main seller owner. System-user eligibility is checked for each order's store(s); if no system user qualifies within 200 m, the active main seller owner is assigned without a location check. If there are multiple eligible system users, they are served fairly according to their most recent assignment; each may hold only one active order at a time.
- Both system users and the owner use their own authenticated user ID as the offer recipient.
- Orders remain unassigned only when there is no nearby eligible system user and no active, available main seller owner. New heartbeats, pending-order polling, and new-order events retry assignment.
- There is currently no accept timeout. A pending offer remains assigned until its order is accepted/dispatched or otherwise processed; it is not reassigned because the recipient's presence becomes stale. Do not show a server countdown.
- There is no seller-order offer-revoked/cancelled push type yet. Presence expiration or order cancellation does not currently publish a push telling the app to close the popup. Refresh/reconcile against the API when the app resumes or the user acts; do not rely on a cancellation push.

The radius check uses coordinates reported by the app/browser. The frontend must request device/browser location permission and send GPS coordinates; do not use a typed address or customer coordinates.

## Main seller: system-user availability dashboard

The main seller owner can view the web dashboard at:

```text
GET /seller/orders/popup-users
GET /seller/orders/popup-users/data
```

On `/seller/orders`, the main seller owner has a **System user availability** button beside **Rider dispatch**. It opens the standalone `/seller/orders/popup-users` page, which contains only the system-user availability dashboard. The dashboard refreshes every 30 seconds and shows system-user contact and role details, seller membership status, login approval/expiry, popup permission, active/inactive state, last location report, nearest seller store and distance, current assigned order, and whether an FCM token is registered. The owner is the only user allowed to read this dashboard. `/seller/orders/dispatch-center` shows both the rider availability summary and expandable rider details, and the dispatch order counts/table. A single Refresh button refreshes both sections; they also refresh automatically (rider availability every 30 seconds, dispatch status every 15 seconds).

The mobile seller API can retrieve the same dashboard data using:

```http
GET /api/seller/orders/popup-users
Authorization: Bearer <sanctum_access_token>
Accept: application/json
```

Only the main seller owner may call this endpoint. Response (`200`):

```json
{
    "success": true,
    "message": "Seller order popup user availability fetched successfully.",
    "data": {
        "summary": {
            "total_system_users": 3,
            "active_users": 1,
            "eligible_users": 1,
            "inactive_users": 2,
            "busy_users": 0
        },
        "users": [
            {
                "user_id": 24,
                "name": "Store Operator",
                "email": "operator@example.com",
                "mobile": "+911234567890",
                "roles": ["Order Manager"],
                "system_user_status": "active",
                "login_approval_status": "approved",
                "login_approved_until": "2026-10-27T10:00:00+00:00",
                "has_order_popup_permission": true,
                "popup_presence_active": true,
                "location_fresh": true,
                "last_seen_at": "2026-10-26T05:00:00+00:00",
                "nearest_store": {
                    "id": 10,
                    "name": "Store One",
                    "distance_m": 82
                },
                "has_fcm_token": true,
                "is_active": true,
                "is_eligible": true,
                "ineligible_reason": null,
                "current_offer": null
            }
        ],
        "location_freshness_minutes": 2,
        "pickup_radius_meters": 200
    }
}
```

`is_active` means the membership is active and the user has active popup presence with a location heartbeat inside the freshness window. `is_eligible` additionally requires an active account, current login approval, popup permission, a reported location within 200 m of any seller store, and no active offer. Actual assignment is checked against each order's store(s), so the dashboard's nearest-store indication is a general seller-store view, not a guarantee for every order. `ineligible_reason` can be `account_inactive`, `system_user_inactive`, `login_not_approved_or_expired`, `order_popup_permission_missing`, `popup_presence_inactive`, `location_stale`, `location_or_store_unavailable`, `outside_200m_radius`, or `order_in_progress`.

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

The seller app can register an FCM token during seller login if supported, and must call `POST /api/seller/fcm-token` on every authenticated app start and Firebase token refresh.

When a system user opens the order popup feature and grants location access, start a background-capable location subscription and call:

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

The main seller owner does not need to grant location access. While the popup is active, the owner should send `{ "active": true }` immediately and refresh that presence at least every 60 seconds. The owner is used only as the fallback when no eligible system user is within 200 m; the owner's coordinates are ignored. Continue to send `{ "active": false }` when the owner disables popup alerts or logs out.

On logout, when the user disables order alerts, or when location permission is revoked, send:

```json
{ "active": false }
```

The current `POST /api/seller/logout` endpoint does not itself clear popup presence and does not delete the stored FCM token. Send `active: false` before logout, as planned. Keeping the device token is intentional so it can be reused/refreshed on the next authenticated session; no new offers should be assigned while presence is inactive/stale.

Validation failure returns `422`; when supplied, latitude must be between -90 and 90 and longitude between -180 and 180. Coordinates are required for system users to qualify for nearby orders, but the main seller owner can activate presence without coordinates for fallback assignment. An unauthenticated request returns `401`; a user without popup permission returns `403`. Presence older than two minutes is not eligible for a new assignment. Staleness does not remove or revoke an offer that has already been assigned.

The seller web panel calls the equivalent session-authenticated endpoint. System users must grant location permission to qualify for nearby orders. The main seller owner is activated as a fallback without location permission and receives orders only when no eligible system user is nearby.

## Seller FCM token registration

Register the current device token after login, when Firebase refreshes it, and on app startup while authenticated:

```http
POST /api/seller/fcm-token
Authorization: Bearer <sanctum_access_token>
Accept: application/json
Content-Type: application/json
```

Request:

```json
{
    "fcm_token": "<current-device-fcm-token>",
    "device_type": "android"
}
```

`device_type` accepts `android`, `ios`, or `web`. Success (`200`):

```json
{
    "success": true,
    "message": "FCM token registered successfully.",
    "data": {
        "device_type": "android"
    }
}
```

An invalid request returns `422`, a user without a seller account returns `404`, and an unauthenticated request returns `401`. Token registration updates the current user's stored token using the existing shared auth-token storage behavior.

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

Request-shape validation errors also return `422`. Missing bag returns `422` with `data.bag_required: true`. If an offer was assigned to another user, popup state-changing actions (accept, assign bag, verify/prepare, and per-item status updates) return `409`; refresh the pending endpoint and do not retry that seller order. Unauthenticated requests return `401`; an order not owned by the seller returns `404`; insufficient permission returns `403`.

The assigned-to-another-user `409` response is:

```json
{
    "success": false,
    "message": "This order popup is assigned to another system user.",
    "data": {
        "code": "seller_order_offer_assigned_to_another_user"
    }
}
```

Use `data.code` rather than message text to handle this conflict. Other validation errors are `422` and may have a different response shape.

## Frontend implementation checklist

1. Register and refresh the device's FCM token during authenticated seller app sessions.
2. Check popup permission (`order.popup`) or the default seller-owner role before rendering the popup.
3. Request device location permission, activate popup presence, and heartbeat every 60 seconds while active, including in the background where the platform permits.
4. Handle `seller_new_order` notifications by opening the popup and refreshing the authenticated pending endpoint; do not trust push data as the order source. The main seller owner receives this only as fallback when no eligible system user is available for the order.
5. Display only the order returned to the authenticated user. Another system user will not receive the same active seller order.
6. Accept, verify each item, assign a bag, and dispatch in that order.
7. Keep sending location heartbeats until the user disables popup availability or logs out. Stop tracking and send `active: false` on those transitions.
8. The main seller owner can use `/seller/orders/popup-users` (web) or `GET /api/seller/orders/popup-users` (mobile) to monitor system-user status, eligibility, location, current assignment, and push-token registration.
9. After a successful dispatch, refresh immediately; the same user may be assigned the next nearby queued order.
