# Seller App Order Popup Integration Guide

This document is the implementation contract for the seller mobile app team. It defines the assignment rules, app lifecycle, endpoint paths, request/response structures, push payloads, and error handling for the seller order popup. The backend creates a durable offer for one eligible seller user per seller order; it does not return the shared seller-wide incoming-order queue to every user.

## Assignment behavior

- An incoming seller order is offered to one eligible system user first. A system user must have `order.popup` permission, an active seller membership, a current login approval, an active account/presence, a fresh location, and be within 200 m of a store in the seller order.
- The main seller owner is a fallback recipient only when no eligible system user is within 200 m of that order's store(s). The owner must have active, fresh popup presence and must not already have an active popup offer; the owner's location is not required or checked.
- A system user must also have an active seller membership and a currently approved login.
- A location is considered fresh for two minutes. A stale presence is excluded from new assignment; the presence row itself is not automatically deactivated.
- Each user can hold one pending or accepted popup offer at a time. Additional orders stay unassigned until the user dispatches the current order.
- From the seller orders page, only the main seller owner can take over a pending offer currently assigned to a system user. The owner can do this before the system user accepts; the offer is reassigned to the owner, and the system user's pending popup closes on its next poll. A high-priority `seller_order_offer_reassigned` push is also sent to the former recipient's registered devices. Offers already accepted by a system user cannot be taken over. The owner still cannot hold a second active order.
- After `verify-and-prepare` succeeds, that offer is completed and the backend immediately tries to assign the next eligible order to that user.
- The one-order-at-a-time limit is shared across regular and wholesale orders.
- Offers are committed before push delivery. Push failure is logged; the assigned offer remains available from the pending endpoint. In particular, a main-seller fallback offer stays assigned and pollable even if that owner's FCM token is missing or push delivery fails.
- Wholesale orders are offered only during the existing popup window: the delivery-slot end time is now through 30 minutes from now.
- A nearby eligible system user always takes priority over the main seller owner. System-user eligibility is checked for each order's store(s); if no system user qualifies within 200 m, the active main seller owner is assigned without a location check. If there are multiple eligible system users, they are served fairly according to their most recent assignment; each may hold only one active order at a time.
- Both system users and the owner use their own authenticated user ID as the offer recipient.
- Orders remain unassigned only when there is no nearby eligible system user and no active, available main seller owner. New heartbeats, pending-order polling, and new-order events retry assignment.
- There is currently no accept timeout. A pending offer remains assigned until its order is accepted/dispatched or otherwise processed; it is not reassigned because the recipient's presence becomes stale. Do not show a server countdown.
- There is no general seller-order offer-revoked/cancelled push type. The `seller_order_offer_reassigned` push is sent only when the main seller takes over a pending offer. Presence expiration or order cancellation does not publish a push telling the app to close the popup; refresh/reconcile against the API when the app resumes or the user acts.

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

`is_active` means the membership and popup presence are active; check `location_fresh` separately because an active presence can have a stale heartbeat. `is_eligible` additionally requires an active account, current login approval, popup permission, fresh coordinates within 200 m of any seller store, and no active offer. Actual assignment is checked against each order's store(s), so the dashboard's nearest-store indication is a general seller-store view, not a guarantee for every order. `ineligible_reason` can be `account_inactive`, `system_user_inactive`, `login_not_approved_or_expired`, `order_popup_permission_missing`, `popup_presence_inactive`, `location_stale`, `location_or_store_unavailable`, `outside_200m_radius`, or `order_in_progress`.

## Authentication and base URLs

### Seller mobile app

Prepend the deployment's API base URL to each path below. All `/api/seller/...` endpoints below require:

```http
Authorization: Bearer <sanctum_access_token>
Accept: application/json
Content-Type: application/json
```

The authenticated user must belong to the seller. The API enforces `order.popup` (the default seller role is allowed without the explicit permission).

Successful endpoints use the JSON envelope `{ "success": true, "message": "...", "data": ... }`. A business-rule error usually uses `{ "success": false, "message": "...", "data": ... }`. Do not assume all validation failures use that envelope: Laravel request validation can return HTTP `422` with `{ "message": "...", "errors": { "<field>": ["..."] } }`. Treat HTTP status and `data.code` (when present) as the stable way to classify errors; never branch on translated message text.

Popup API quick reference:

| Method | Path | Request body | Who can use it |
|---|---|---|---|
| `POST` | `/api/seller/fcm-token` | `fcm_token`, `device_type` | Any authenticated seller account |
| `POST` | `/api/seller/orders/popup-presence` | `active`; coordinates for system users | Main seller owner or user with `order.popup` |
| `GET` | `/api/seller/orders/pending-regular?order_mode=regular` | None | Main seller owner or user with `order.popup` |
| `GET` | `/api/seller/orders/pending-regular?order_mode=wholesale&popup=1` | None | Main seller owner or user with `order.popup` |
| `POST` | `/api/seller/orders/{seller_order_id}/takeover-pending-offer` | None | Main seller owner only |
| `POST` | `/api/seller/orders/{seller_order_id}/accept-items` | None | Authorized seller order processor |
| `POST` | `/api/seller/orders/{seller_order_id}/assign-bag` | `{ "barcode": "..." }` | Authorized seller order processor |
| `POST` | `/api/seller/orders/{seller_order_id}/verify-and-prepare` | Full `items` array | Authorized seller order processor |
| `GET` | `/api/seller/orders/popup-users` | None | Main seller owner only |

`{seller_order_id}` is the seller's `seller_orders.id`, not the global order ID or an order-item ID.

### Orders page and Process order action

The paginated seller order list is:

```http
GET /api/seller/orders?page=1&per_page=15&order_mode=regular
```

Optional query parameters are `status`, `payment_type`, `range`, `search`, `sort_by` (`id|order_id|price|status|created_at`), `sort_dir` (`asc|desc`), and `store_id` (must belong to the seller). It returns order-item rows, so group rows by `seller_order_id` when rendering one seller-order-level **Process order** button. Show the action if at least one row in that seller order has `status` equal to `awaiting_store_response` or `accepted`. The main seller can process an order currently offered to a system user; the takeover endpoint verifies it is still pending and transfers it to the owner. System users should only process orders returned by their own pending endpoint.

Success (`200`) has this shape; fields below show the minimum useful subset for the popup list:

```json
{
    "success": true,
    "message": "Orders fetched successfully",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "order_item_id": 9901,
                "seller_order_id": 701,
                "bag": null,
                "created_at": "2026-10-26T04:50:00.000000Z",
                "order": {
                    "id": 8801,
                    "image": "https://example.com/product.jpg",
                    "uuid": "f96a70e4-9801-4f4a-98b2-0ae9b0a8f3d9",
                    "buyer_name": "Customer Name",
                    "payment_method": "cod",
                    "order_mode": "regular",
                    "is_rush_order": false,
                    "status": "global_order_status"
                },
                "product": {
                    "id": 440,
                    "title": "Example Product",
                    "variant": "500 g"
                },
                "store": {
                    "name": "Store One"
                },
                "sku": "EXAMPLE-500G",
                "quantity": 2,
                "subtotal": {
                    "raw": 499.0,
                    "formatted": "₹499.00"
                },
                "is_gift": false,
                "status": "awaiting_store_response"
            }
        ]
    }
}
```

The `order.status` above is the global order status and is not the seller-item status; use the top-level row `status` to decide whether the seller order can be processed. After the owner taps **Process order**: call `POST /api/seller/orders/{seller_order_id}/takeover-pending-offer`, then refetch the selected seller order from `pending-regular` and open its popup. If takeover returns `409`, do not open it; refresh both the order list and pending queue.

### Seller web panel

The web panel uses its existing authenticated session and CSRF protection. The equivalent routes are:

```text
GET  /seller/orders/pending-regular
POST /seller/orders/popup-presence
POST /seller/orders/{seller_order_id}/accept-items
POST /seller/orders/{seller_order_id}/assign-bag
POST /seller/orders/{seller_order_id}/verify-and-prepare
POST /seller/orders/{seller_order_id}/takeover-pending-offer
```

The web response for the pending endpoint uses the legacy shape described in the pending-orders section below. The seller layout reports location automatically while the popup is enabled.

The app's corresponding API paths are:

```text
POST /api/seller/orders/popup-presence
GET  /api/seller/orders/pending-regular?order_mode=regular
POST /api/seller/orders/{seller_order_id}/takeover-pending-offer
POST /api/seller/orders/{seller_order_id}/accept-items
POST /api/seller/orders/{seller_order_id}/assign-bag
POST /api/seller/orders/{seller_order_id}/verify-and-prepare
GET  /api/seller/orders/popup-users
```

The mobile API requires a Sanctum bearer token; the web routes require the authenticated web session and CSRF protection. The takeover endpoint is available only to the main seller owner. It transfers a pending offer from another user to the owner; it never overrides an offer another user has already accepted. The owner calls it after tapping **Process order** and before loading the order into the popup workflow. No request body is required. A successful response (`200`) is:

```json
{
    "success": true,
    "message": "Pending order offer assigned to the main seller.",
    "data": {
        "seller_order_id": 321,
        "assigned_to_user_id": 8,
        "status": "pending"
    }
}
```

The mobile app must show **Process order** for the main seller for orders in `awaiting_store_response` or `accepted` status, including orders currently assigned to system users. It must call the takeover endpoint before fetching/resuming the order. The system-user **Process order** action must not call takeover and only resumes an order still assigned to that user. Once an offer is reassigned, the owner accepts through the normal `accept-items` step before item verification, bag assignment, and dispatch.

Possible takeover conflicts (`409`) use the standard response envelope with a stable `data.code`:

```json
{
    "success": false,
    "message": "This order has already been accepted by another user.",
    "data": {
        "code": "seller_order_offer_already_accepted"
    }
}
```

Other conflict codes are `seller_order_offer_not_pending` when no active pending offer exists and `seller_owner_has_active_order` when the owner already holds another pending or accepted offer. A non-owner receives `403`; an order outside the authenticated seller returns `404`; an expired/missing bearer token returns `401`.

The other takeover conflict responses use the same envelope:

```json
{
    "success": false,
    "message": "Finish the order currently assigned to you before taking over another order.",
    "data": {
        "code": "seller_owner_has_active_order"
    }
}
```

Non-owner response (`403`):

```json
{
    "success": false,
    "message": "Unauthorized action.",
    "data": []
}
```

An order not found for this seller returns `404` and `data.code` is `seller_order_not_found`.

When a pending offer is taken over, the former recipient receives a high-priority notification with this routing data:

```json
{
    "type": "seller_order_offer_reassigned",
    "seller_order_id": "321",
    "order_id": "987",
    "order_number": "NM-20261027-ABC123",
    "order_mode": "regular"
}
```

Dismiss the matching pending popup and refresh the pending-orders endpoint when this event is received. On the web panel, the same state is reconciled through regular polling.

The takeover response means the offer owner has changed, but does not mean the order is accepted. Keep the workflow in the **Accept order** stage and call `accept-items` only after the owner explicitly accepts in the UI.

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

Success (`200`) has the same response shape as activation; `active` is `false`, and `latitude`/`longitude` are the last stored values or `null`:

```json
{
    "success": true,
    "message": "Seller popup presence updated.",
    "data": {
        "active": false,
        "latitude": "21.14580000",
        "longitude": "79.08820000",
        "last_seen_at": "2026-10-26T05:10:00+00:00"
    }
}
```

Popup presence is not automatically deactivated when the app ends its authenticated session. Send `active: false` before logout. The stored FCM token is retained for use on the next authenticated session; no new offers should be assigned while presence is inactive or stale.

Validation failure returns `422`; when supplied, latitude must be between -90 and 90 and longitude between -180 and 180. Coordinates are required for system users to qualify for nearby orders, but the main seller owner can activate presence without coordinates for fallback assignment. An unauthenticated request returns `401`; a user without popup permission returns `403`. Presence older than two minutes is not eligible for a new assignment. Staleness does not remove or revoke an offer that has already been assigned.

The seller web panel calls the equivalent session-authenticated endpoint. System users must grant location permission to qualify for nearby orders. The main seller owner is activated as a fallback without location permission and receives orders only when no eligible system user is nearby.

The main seller owner can minimize the incoming-order popup without releasing the assigned offer. A persistent **Continue order** button reopens the same workflow; minimizing is not available to system users. On `/seller/orders`, pending and accepted order rows have a **Process order** action that opens the selected order in the same popup workflow (accept, scan/verify every item, assign a bag, and complete dispatch). For a main-seller takeover, the owner must claim the pending offer before opening it. The server continues enforcing offer ownership for each operation.

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
            "sound": "default"
        }
    },
    "apns": {
        "headers": {
            "apns-priority": "10",
            "apns-push-type": "alert"
        },
        "payload": {
            "aps": {
                "sound": "default",
                "content-available": 1
            }
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

Use the two modes independently:

```http
GET /api/seller/orders/pending-regular?order_mode=regular
GET /api/seller/orders/pending-regular?order_mode=wholesale&popup=1
```

The returned `seller_order_id` is the ID used in all popup action URLs. `order_id` is the customer/global order ID and must not be used in those URLs. `order_item_id` is the global order-item ID used only in the verification request.

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
                        "rack_number": "A-12",
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

`rack_number` is the product's optional storage location (`string|null`). Show it prominently beside the product name in the order summary and while scanning/verifying the item so the picker can find the correct shelf. It is also available on seller product create/update and product detail responses; send it as an optional string (maximum 100 characters) when editing product information.

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

Unknown or unavailable bags return `422`. A bag already assigned to this order is accepted idempotently if the submitted barcode matches; a different barcode returns `422`. The first assignment response includes `bag.assigned_at`; an idempotent response may omit that field. Once assigned, the popup should show **Dispatch order**.

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

1. Authenticate with the existing seller login and send the returned Sanctum token as `Authorization: Bearer <token>` on every API request. Refresh/register the current FCM token on app start and each Firebase token refresh.
2. The main seller is identified by the seller record's `user_id` matching the authenticated user ID. Only that user may see the popup-user dashboard and use the takeover API. System users need `order.popup` permission to use the popup.
3. Before enabling alerts, request notification permission. For system users, also request location permission; send a location-bearing `active: true` heartbeat and refresh it at least every 60 seconds. The owner sends `active: true` without coordinates and does not need location permission.
4. On logout, alert opt-out, or location permission revocation, send `active: false` and stop location tracking. Popup presence is not cleared automatically by logout.
5. Poll the regular and wholesale pending endpoints while alerts are active (about every 5 seconds in the foreground), on app resume, and on notification arrival. Treat the response as authoritative and reconcile/remove local popup entries that no longer appear.
6. Handle `seller_new_order` by refreshing assigned orders; handle `seller_order_offer_reassigned` by dismissing that `seller_order_id` and refreshing. A push is a hint, not an order record. Do not act on notification payload alone.
7. Display only orders returned for the authenticated user. On the seller orders page, show **Process order** for eligible awaiting/accepted seller orders. For the main seller, call `takeover-pending-offer` before opening the selected order, even when it is currently assigned to a system user. A takeover is available only while the offer is still pending; if a system user accepted first, show the conflict and refresh. System users must not call the takeover endpoint.
8. Use the `seller_order_id` for accept, takeover, bag, and dispatch endpoints. Do not allow scanning/dispatch until the user accepts, verifies every item, and assigns a bag.
9. Treat expected barcodes as comparison data only: do not display them to the person scanning. Require exact barcode and quantity for every item and submit the full set in one `verify-and-prepare` request.
10. Use HTTP status and `data.code` to handle conflicts and auth failures. In particular, on `409`, stop actions on that order and refresh; do not automatically retry acceptance, takeover, or dispatch.
11. The main seller may use `GET /api/seller/orders/popup-users` to monitor system-user activity and eligibility. Refresh after presence changes and periodically while viewing the dashboard.
12. After successful dispatch, refresh pending orders immediately; the same user may be assigned the next eligible queued order.
