# Customer App: Product Return API

These endpoints let an authenticated customer request a return for one delivered order item and cancel an eligible return request. All routes require a Sanctum bearer token.

```http
Authorization: Bearer <customer-access-token>
Accept: application/json
```

Base path: `/api/user/orders`

## Check return eligibility

Order details include return fields on each `order_items[]` object. Use these values to decide whether to show the return action; the API remains authoritative and can reject a request if the deadline or status has changed.

```json
{
    "id": 781,
    "status": "delivered",
    "return_eligible": true,
    "return_deadline": "2026-10-14",
    "returns": []
}
```

Relevant fields:

- `id`: order item ID to use in return endpoints.
- `status`: order-item status. Return creation requires the item or its order to be delivered.
- `return_eligible`: product/order-item return eligibility flag.
- `return_deadline`: last date the item can be returned; may be null.
- `returns`: existing return records when included by the order endpoint. A non-cancelled existing return prevents another request.

## Create return request

```http
POST /api/user/orders/items/{orderItemId}/return
Content-Type: multipart/form-data
```

Multipart fields:

| Field      | Type    | Required | Rules                                |
| ---------- | ------- | -------: | ------------------------------------ |
| `reason`   | string  |      Yes | Maximum 1000 characters              |
| `images[]` | file(s) |       No | Image files: JPG, JPEG, PNG, or WEBP |

Example using cURL:

```bash
curl -X POST "https://api.example.com/api/user/orders/items/781/return" \
  -H "Authorization: Bearer <customer-access-token>" \
  -H "Accept: application/json" \
  -F "reason=The package was damaged when delivered" \
  -F "images[]=@damage-1.jpg" \
  -F "images[]=@damage-2.jpg"
```

Success response, HTTP `200`:

```json
{
    "success": true,
    "message": "Return request submitted.",
    "data": {
        "id": 45,
        "order_item_id": 781,
        "order_id": 320,
        "user_id": 196,
        "seller_id": 12,
        "store_id": 8,
        "delivery_boy_id": null,
        "reason": "The package was damaged when delivered",
        "seller_comment": null,
        "images": ["https://api.example.com/storage/returns/damage-1.jpg"],
        "refund_amount": 245.5,
        "pickup_status": "pending",
        "return_status": "requested",
        "seller_approved_at": null,
        "picked_up_at": null,
        "received_at": null,
        "refund_processed_at": null,
        "created_at": "2026-10-07 12:30:00",
        "updated_at": "2026-10-07 12:30:00"
    }
}
```

The server determines `refund_amount`; do not send it from the app. The `images` array contains uploaded image URLs.

A valid request is accepted only when:

- The item belongs to the authenticated customer.
- The item is marked `return_eligible`.
- The order item or its order has delivered status.
- The return deadline has not expired.
- There is no existing return except a previously cancelled request.

Business-rule failure response, generally HTTP `200` with `success: false`:

```json
{
    "success": false,
    "message": "This product is not returnable.",
    "data": []
}
```

The message varies by failure. Possible reasons include item not found, product not returnable, order not delivered, return deadline expired, or a return already requested. Invalid multipart fields/files are validation errors (HTTP `422`); show field errors returned by Laravel.

## Cancel a return request

No request body is required.

```http
POST /api/user/orders/items/{orderItemId}/return-cancel
Authorization: Bearer <customer-access-token>
Accept: application/json
```

Example:

```bash
curl -X POST "https://api.example.com/api/user/orders/items/781/return-cancel" \
  -H "Authorization: Bearer <customer-access-token>" \
  -H "Accept: application/json"
```

Success response, HTTP `200`:

```json
{
    "success": true,
    "message": "Return request cancelled.",
    "data": {
        "id": 45,
        "order_item_id": 781,
        "order_id": 320,
        "user_id": 196,
        "seller_id": 12,
        "store_id": 8,
        "delivery_boy_id": null,
        "reason": "The package was damaged when delivered",
        "refund_amount": "245.50",
        "seller_comment": null,
        "pickup_status": "pending",
        "return_status": "cancelled",
        "seller_approved_at": null,
        "picked_up_at": null,
        "received_at": null,
        "refund_processed_at": null,
        "created_at": "2026-10-07T12:30:00.000000Z",
        "updated_at": "2026-10-07T12:35:00.000000Z",
        "images": ["https://api.example.com/storage/returns/damage-1.jpg"]
    }
}
```

The cancellation endpoint currently returns the serialized model (so fields such as `refund_amount` and timestamps may serialize differently than the create endpoint's resource). Refresh the order details after cancellation and use its item `returns` list for the resulting status.

Cancellation is allowed only while `return_status` is `requested` or `seller_approved`. If there is no active return, or it has moved beyond those statuses, the endpoint returns `success: false` with an explanatory `message` and empty `data`.

## Status values

`return_status` can be:

- `requested`
- `seller_approved`
- `seller_rejected`
- `pickup_assigned`
- `picked_up`
- `received_by_seller`
- `refund_processed`
- `completed`
- `cancelled`

`pickup_status` can be:

- `pending`
- `assigned`
- `picked_up`
- `delivered_to_seller`
- `cancelled`

Treat the status strings as machine-readable values and display localized labels in the app. The create response is the `OrderItemReturnResource`; cancellation currently returns a raw serialized `OrderItemReturn` model.
