# Seller Order Popup API

The seller panel uses the session-authenticated web endpoints below. The seller mobile API exposes matching actions under `/api/seller/orders` and requires Sanctum authentication.

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

## 3. Verify every ordered item in the popup

This step is local to the popup and does not call an endpoint. The seller scans/enters each item barcode and confirms quantity with arrow keys. The UI shows field-specific errors, advances immediately on a valid item, and tracks all item states in the checklist. The barcode is compared internally and is not shown as expected data.

## 4. Scan and assign a bag

Call this after all item checks pass and before dispatch. Only an available bag barcode in this seller's inventory can be assigned. A bag already assigned to this same order is accepted idempotently; bags assigned elsewhere and unknown barcodes are rejected.

```http
POST /seller/orders/701/assign-bag
Content-Type: application/json
Accept: application/json
```

Request:

```json
{
    "barcode": "BAG-000033"
}
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
            "assigned_at": "2026-10-01T10:15:00.000000Z"
        }
    }
}
```

An unknown or already-assigned barcode returns `422` with a message indicating the bag is not available in the seller's pool. Once assigned, the popup shows **Dispatch order**.

## 5. Dispatch the order

The final request submits all scanned item values. The server rechecks every barcode and quantity and verifies that this seller order has an assigned bag before changing any accepted items to `preparing`.

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

Each accepted order item must appear exactly once. `quantity` must be an integer equal to the ordered quantity; `barcode` must exactly match the barcode stored on the ordered product variant.

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

Request-shape validation errors also return `422`. If there is no assigned bag, the endpoint returns `422` with `bag_required: true`. Unauthenticated requests return `401`; an order not owned by the seller returns `404`. The legacy per-item preparing endpoint also requires the matching item barcode, quantity, and an assigned bag.
