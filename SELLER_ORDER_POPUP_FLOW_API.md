# Seller Order Popup API

The seller panel uses the session-authenticated web endpoints below. The seller mobile API exposes matching accept and verify actions under `/api/seller/orders` and requires Sanctum authentication.

## 1. Fetch incoming or resumable orders

```http
GET /seller/orders/pending-regular?order_mode=regular
Accept: application/json
```

Use `order_mode=wholesale` for wholesale orders. The `popup=1` query is used by the panel for the wholesale time-window filter. The response includes items awaiting acceptance and previously accepted items that still need barcode verification, so an interrupted flow can resume.

```json
{
    "data": [
        {
            "seller_order_id": 701,
            "order_id": 8801,
            "order_number": "NM-20260930-AB3XK9ZM",
            "order_mode": "regular",
            "created_at": "2026-09-30T10:00:00.000000Z",
            "customer": {
                "name": "Customer Name",
                "phone": "+911234567890",
                "address": "12 Example Road, Nagpur, Maharashtra, 440001"
            },
            "payment_method": "cod",
            "total": 499.0,
            "delivery": "30 Sep 10:30 - 11:00",
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
                    "status": "awaiting_store_response",
                    "image": "https://example.com/product.jpg",
                    "quantity": 2,
                    "subtotal": 499.0
                }
            ]
        }
    ],
    "count": 1,
    "order_mode": "regular"
}
```

`status` is `awaiting_store_response` for a new decision or `accepted` when the seller should resume item verification. `barcode` is the expected barcode for internal comparison and must not be displayed in the popup. `sku`, `variant_weight`, and `variant_dimensions` provide additional identifying details for the seller.

## 2. Accept all items in the seller order

```http
POST /seller/orders/701/accept-items
Accept: application/json
```

No request body is required. This accepts all items still awaiting a seller decision in this seller order. The action is idempotent for items that were already accepted.

Success (`200`):

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

## 3. Verify every item and mark the seller order preparing

The panel checks each scanned barcode and arrow-key quantity locally, shows errors under only the mismatched field, and advances to the next item immediately after a successful check. A checklist shows each item as unchecked, passed, or failed. Only after every item passes does the panel submit the full set. The server independently validates every value and the exact set of accepted item IDs before changing any statuses.

```http
POST /seller/orders/701/verify-and-prepare
Content-Type: application/json
Accept: application/json
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

Each accepted order item must appear exactly once. `quantity` must be an integer equal to the ordered quantity. `barcode` must exactly match the barcode stored on the ordered product variant.

Success (`200`):

```json
{
    "success": true,
    "message": "All item checks passed and the order is now preparing.",
    "data": {
        "seller_order_id": 701,
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

Mismatch or incomplete item set (`422`):

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

Request-shape validation errors also return `422`. Unauthenticated requests return `401`; an order not owned by the seller returns `404`. Preparing through the existing single-item status endpoints also requires matching `barcode` and `quantity` request values.
