# Seller Rider Dispatch API

The backend owns rider selection. It reserves each prepared, bagged order to one available rider and sends that rider a data-only `new_order` push. A seller can monitor the queue and mark a ready order for seller self-delivery when needed.

## Dispatch rules

- Pickup coordinates come from the store attached to the prepared seller-order item (used as the pickup warehouse).
- Riders must be `active`, have a registered FCM token, have reported a location within the last 2 minutes, and be within 200 m of the pickup store.
- A rider with a pending offer or an assignment in `assigned`/`in_progress` state is excluded.
- Candidate riders and ready orders are randomized; each rider receives at most one pending order.
- Reservation sets `orders.delivery_boy_id` and `orders.delivery_assignment_type = rider` before sending the push.
- Dispatch is retried when an order becomes ready, a rider goes active, or an active rider reports a location update. The rider app should heartbeat `POST /api/delivery-boy/update-current-location` while on duty and after returning to a pickup area.
- There is no timeout. An offer remains assigned until accepted, cancelled, or manually handled. A missing nearby rider appears in the dispatch dashboard; no seller push event is currently sent.

All `/api/seller` endpoints below require `Authorization: Bearer <seller_sanctum_token>` and `Accept: application/json`.

## Seller orders page: rider availability

The seller web orders page displays active, available-within-radius, and inactive rider counts, plus an expandable rider details table. It refreshes this snapshot every 30 seconds.

```http
GET /api/seller/orders/rider-availability
Authorization: Bearer <seller_access_token>
Accept: application/json
```

This bearer-token endpoint is protected by seller authentication and seller approval middleware. It returns data only for the authenticated seller's pickup stores and delivery zones.

Success (`200`):

```json
{
    "success": true,
    "message": "Rider availability fetched successfully.",
    "data": {
        "summary": {
            "active_riders": 4,
            "available_riders_in_radius": 2,
            "inactive_riders": 3
        },
        "riders": [
            {
                "id": 5,
                "name": "Rider Name",
                "status": "active",
                "verification_status": "verified",
                "delivery_zone": "Dharampeth",
                "is_available_in_radius": true,
                "nearest_pickup_distance_m": 84,
                "location_reported_at": "2026-10-25T10:00:00+00:00",
                "location_age_seconds": 18,
                "unavailable_reason": null
            },
            {
                "id": 6,
                "name": "Another Rider",
                "status": "active",
                "verification_status": "verified",
                "delivery_zone": "Dharampeth",
                "is_available_in_radius": false,
                "nearest_pickup_distance_m": null,
                "location_reported_at": "2026-10-25T09:54:00+00:00",
                "location_age_seconds": 378,
                "unavailable_reason": "location_stale"
            }
        ]
    }
}
```

`available_riders_in_radius` uses the dispatch eligibility rules: active and verified, registered FCM token, location report within 2 minutes, within 200 m of a seller pickup store, no pending offer, and no `assigned`/`in_progress` delivery. Rider rows do not expose exact GPS coordinates. `unavailable_reason` values are `inactive`, `not_verified`, `no_fcm_token`, `location_stale`, `no_pickup_store`, `outside_pickup_radius`, `pending_offer`, and `active_delivery`.

## 1. Seller dispatch dashboard

```http
GET /api/seller/orders/dispatch-dashboard
```

Success (`200`):

```json
{
    "success": true,
    "message": "Dispatch dashboard fetched successfully.",
    "data": {
        "summary": {
            "ready_orders": 2,
            "no_nearby_riders": 1,
            "assigned_orders": 1
        },
        "orders": [
            {
                "order_id": 183,
                "order_number": "NM-20261025-AB3XK9ZM",
                "status": "no_nearby_riders",
                "delivery_boy_id": null,
                "delivery_boy_name": null,
                "pickup": "Sharma Kirana",
                "pickup_store_id": 12,
                "pickup_latitude": "21.14580000",
                "pickup_longitude": "79.08820000",
                "nearby_available_riders": 0,
                "bag_barcode": "BAG-000183",
                "created_at": "2026-10-25T10:00:00+00:00"
            },
            {
                "order_id": 184,
                "order_number": "NM-20261025-CD4LM2PQ",
                "status": "awaiting_rider_acceptance",
                "delivery_boy_id": 5,
                "delivery_boy_name": "Rider Name",
                "pickup": "Sharma Kirana",
                "pickup_store_id": 12,
                "pickup_latitude": "21.14580000",
                "pickup_longitude": "79.08820000",
                "nearby_available_riders": 2,
                "bag_barcode": "BAG-000184",
                "created_at": "2026-10-25T10:01:00+00:00"
            }
        ]
    }
}
```

Order `status` values:

- `no_nearby_riders`: seller alert state; use the self-delivery action or wait for an eligible rider to report a location.
- `waiting_for_dispatch`: an eligible rider is nearby and dispatch is pending.
- `awaiting_rider_acceptance`: reserved and pushed to a rider; the alert remains active until a person acts.
- `rider_assigned`: rider accepted the order.
- `seller_self_delivery`: seller took responsibility for delivery.

## 2. Mark an order for seller self-delivery

This action is allowed for a ready bagged order owned by the authenticated seller. It is rejected if a rider already accepted the order. If a rider had a pending offer, that rider receives `order_alert_cancel` with `reason: reassigned`.

```http
POST /api/seller/orders/183/self-deliver
```

No body is required.

Success (`200`):

```json
{
    "success": true,
    "message": "Order marked for seller self-delivery.",
    "data": { "order_id": 183 }
}
```

Conflict (`409`) when a rider has already accepted the order or it has already been marked for self-delivery:

```json
{
    "success": false,
    "message": "The rider has already accepted this order. Contact dispatch to reassign it.",
    "data": {}
}
```

Not found (`404`) when the order is not owned by the seller:

```json
{
    "success": false,
    "message": "Order not found for this seller.",
    "data": {}
}
```

The panel-only session routes are `GET /seller/orders/dispatch-center`, `GET /seller/orders/dispatch-center/data`, and `POST /seller/orders/{orderId}/self-deliver`. The dispatch page refreshes the dashboard every 15 seconds and exposes the same self-delivery action.

This action only assigns delivery responsibility to the seller. It does not mark order items delivered. The existing seller order status API (`POST /api/seller/orders/{orderItemId}/{status}`) does not support `delivered`; that transition is restricted to the delivery-boy flow. A dedicated seller self-delivery completion endpoint is not implemented. Do not use the seller status API to simulate delivery; completion must be added with the same OTP and COD-payment safeguards required by the existing delivery flow.

## Backend gaps: notifications and re-dispatch

- There is no seller FCM event for `no_nearby_riders`. The seller app can currently discover this state by polling `GET /api/seller/orders/dispatch-dashboard`. A seller-side `dispatch_alert` event should be added before relying on push delivery while the app is backgrounded or closed.
- There is no seller API to cancel a pending rider offer and re-dispatch the order. `POST /api/seller/orders/{orderId}/self-deliver` is currently the available fallback. A manual retry endpoint should cancel and notify the rider only when the offer is still pending, then retry normal dispatch; it must reject offers already accepted by a rider.

## 3. Rider app integration

The rider accepts the order with the existing endpoint:

```http
POST /api/delivery-boy/orders/{orderId}/accept
Authorization: Bearer <rider_sanctum_token>
Accept: application/json
```

Available orders now returns only the rider's own backend reservations. An order not reserved to that rider returns HTTP `409`. The rider app must also consume the data-only `new_order` and `order_alert_cancel` events documented in `RIDER_ORDER_POPUP_FLOW_API.md`.

## 4. Seller app workflow

1. Poll `GET /api/seller/orders/dispatch-dashboard` or subscribe/refresh from the seller dispatch page.
2. Show an alert when `summary.no_nearby_riders` is greater than zero; identify the affected orders by their `no_nearby_riders` status.
3. If the seller chooses self-delivery, call the corresponding `POST .../{orderId}/self-deliver` endpoint and stop offering that order for rider dispatch.
4. For `awaiting_rider_acceptance`, show the reserved rider and keep monitoring. The server does not time out or clear the reservation automatically.
5. Keep rider locations fresh through the rider app heartbeat; the backend retries dispatch on location updates.
