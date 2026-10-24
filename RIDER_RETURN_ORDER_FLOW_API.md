# Rider return order flow

All endpoints below use the rider's Sanctum token:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

The server sends a six-digit OTP to the customer when a rider accepts a return. After the rider verifies it while collecting the package, the server sends a different OTP to the seller. OTP hashes are stored server-side and are never included in API responses. Both handoff OTPs are mandatory.

## 1. List returns available to accept

```http
GET /api/delivery-boy/return-pickups/available?per_page=10
```

`per_page` is optional (default `10`).

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
    "pickups": [
      {
        "id": 731,
        "order_id": 8801,
        "order_item_id": 9901,
        "user_id": 501,
        "seller_id": 62,
        "store_id": 10,
        "delivery_boy_id": null,
        "reason": "Wrong size",
        "refund_amount": "499.00",
        "seller_comment": null,
        "pickup_status": "pending",
        "return_status": "seller_approved",
        "seller_approved_at": "2026-10-24 10:00:00",
        "picked_up_at": null,
        "received_at": null,
        "refund_processed_at": null,
        "images": ["https://example.test/return-photo.jpg"],
        "delivery_route": {
          "total_distance": 3.4,
          "route": [10],
          "route_details": []
        },
        "earnings": {
          "total": 45.0,
          "breakdown": {
            "base_fee": 20.0,
            "per_store_pickup_fee": 10.0,
            "distance_based_fee": 10.0,
            "per_order_incentive": 5.0
          }
        },
        "delivery_zone": {"id": 4, "name": "Central"},
        "order": {
          "id": 8801,
          "uuid": "order-uuid",
          "shipping_name": "Customer Name",
          "shipping_phone": "+911234567890",
          "shipping_address_1": "12 Example Road",
          "shipping_address_2": null,
          "shipping_landmark": "Near Example Park",
          "shipping_city": "Nagpur",
          "shipping_state": "Maharashtra",
          "shipping_zip": "440001",
          "shipping_country": "India",
          "shipping_latitude": "21.14580000",
          "shipping_longitude": "79.08820000"
        },
        "order_item": {
          "id": 9901,
          "product": {"id": 440, "name": "Example Product"},
          "variant": {"id": 48, "sku": "EX-500", "title": "500 g"}
        },
        "store": {
          "id": 10,
          "name": "Sharma Kirana",
          "address": "45 Market Road",
          "city": "Nagpur",
          "state": "Maharashtra",
          "zipcode": "440002",
          "country": "India",
          "contact_number": "+919876543210",
          "latitude": "21.15000000",
          "longitude": "79.09000000"
        },
        "user": {
          "id": 501,
          "name": "Customer Name",
          "phone": "+911234567890",
          "email": "customer@example.test"
        },
        "created_at": "2026-10-24 09:30:00",
        "updated_at": "2026-10-24 10:00:00"
      }
    ]
  }
}
```

The customer pin is `order.shipping_latitude`/`shipping_longitude`; the seller pin is `store.latitude`/`longitude`. `delivery_route.route_details` provides backend route data; road directions, alternatives, voice navigation, and arrival prompts are handled by the rider app.

## 2. Accept a return

Call this when the rider swipes **Swipe to Accept Return**:

```http
POST /api/delivery-boy/return-pickups/731/accept
```

No request body is required. On success, the return is assigned to the rider and the server sends the customer a pickup OTP by SMS. The response is `{ "success": true, "message": "...", "data": { "pickup": <pickup object> } }`, where `pickup` has the same shape as an item in the available list and `pickup_status` is `assigned`.

If customer contact is missing or SMS delivery fails, acceptance is not completed:

```json
{
  "success": false,
  "message": "Could not send the pickup OTP to the customer. Please try again.",
  "data": {"error": "otp_delivery_failed"}
}
```

Missing customer contact returns HTTP `422`; SMS delivery failure returns HTTP `503`. Neither case accepts the return.

The app can show the pickup route from the returned object. Use `order.shipping_phone` to call the customer and `store.contact_number` to call the seller.

## 3. Confirm customer handoff (`picked_up`)

After the rider meets the customer and completes any arrival prompt/notification handled by the rider app, collect the package. This backend currently exposes no separate return-arrival notification endpoint. To confirm collection, submit the customer's six-digit OTP:

```http
PUT /api/delivery-boy/return-pickups/731/status
```

```json
{
  "status": "picked_up",
  "otp": "482951"
}
```

The server verifies the customer OTP, changes `pickup_status` to `picked_up`, sets `picked_up_at`, and sends a separate handover OTP to the seller. The response uses the standard wrapper and contains the updated `pickup` object; the `seller_handover_otp_hash` and OTP itself are never returned.

```json
{
  "success": true,
  "message": "Status updated successfully",
  "data": {
    "pickup": {
      "id": 731,
      "pickup_status": "picked_up",
      "picked_up_at": "2026-10-24 10:20:00",
      "return_status": "picked_up"
    }
  }
}
```

The sample shows the changed fields; the actual `pickup` includes the full resource shape described in step 1. An invalid/missing OTP does not change the status and returns HTTP `422`:

```json
{
  "success": false,
  "message": "Invalid OTP",
  "data": {
    "error": "invalid_otp",
    "current": "assigned",
    "requested": "picked_up"
  }
}
```

If the seller's handover OTP cannot be sent, the status remains `assigned`; retry the same request with the customer's OTP after SMS service/contact is restored. The API returns HTTP `503` and `data.error` is `otp_delivery_failed`.

## 4. Confirm seller handoff (`delivered_to_seller`)

On the return-to-seller leg, the app can use the same pickup's `store` coordinates and `contact_number`. When the seller receives the package, submit the seller's separate six-digit OTP:

```http
PUT /api/delivery-boy/return-pickups/731/status
```

```json
{
  "status": "delivered_to_seller",
  "otp": "175204"
}
```

On a valid OTP, the server sets `pickup_status` to `delivered_to_seller`, records `received_at`, completes the return/refund processing, and marks the rider assignment complete. The response wrapper and `pickup` shape match step 3.

Invalid OTP returns HTTP `422` with `data.error: "invalid_otp"` and leaves the return uncompleted. A status out of sequence returns HTTP `409` with `data.error: "invalid_transition"`.

## 5. Rider's active and completed returns

```http
GET /api/delivery-boy/return-pickups/my?per_page=10
GET /api/delivery-boy/return-pickups/731
```

Both use the normal `{ "success": true, "message": "...", "data": ... }` wrapper. The list response contains pagination fields and `data.pickups`; the detail response contains `data.pickup`. Both return the same full pickup resource, including item, customer, store, photos, status, route, and earnings. These endpoints require rider authentication. Accept only succeeds for an available return in the rider's zone; status updates require the return to be assigned to that rider.
