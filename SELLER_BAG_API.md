# Seller Bag Inventory API

All endpoints require a Sanctum-authenticated seller. Send `Authorization: Bearer {token}` for API clients. Bag barcodes are globally unique. Each bag belongs to one seller and can be assigned to at most one seller order. Assigned bags cannot be edited or deleted.

## List bags

```http
GET /api/seller/bags?status=available&search=BAG-&page=1&per_page=25
Accept: application/json
```

Query parameters are optional: `status` is `available` or `assigned`; `search` matches a barcode; `page` defaults to `1`; `per_page` defaults to `25` and is capped at `100`.

```json
{
    "success": true,
    "message": "Bags fetched successfully.",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 25,
        "total": 2,
        "items": [
            {
                "id": 33,
                "barcode": "BAG-000033",
                "status": "available",
                "seller_order_id": null,
                "order_number": null,
                "assigned_at": null,
                "created_at": "2026-10-01T09:00:00.000000Z"
            },
            {
                "id": 34,
                "barcode": "BAG-000034",
                "status": "assigned",
                "seller_order_id": 701,
                "order_number": "NM-20261001-AB3XK9ZM",
                "assigned_at": "2026-10-01T10:15:00.000000Z",
                "created_at": "2026-10-01T09:01:00.000000Z"
            }
        ]
    }
}
```

## Bulk add barcodes

One request can add up to 1,000 bag barcodes. Send a JSON array or newline/comma/semicolon-separated text. The seller UI uses the text form for convenient scanner paste or bulk entry.

```http
POST /api/seller/bags/bulk
Content-Type: application/json
Accept: application/json
```

Array request:

```json
{
    "barcodes": ["BAG-000033", "BAG-000034", "BAG-000035"]
}
```

Text request:

```json
{
    "barcodes": "BAG-000033\nBAG-000034\nBAG-000035"
}
```

Success (`201`); already-used barcodes are skipped and reported:

```json
{
    "success": true,
    "message": "Bag barcode import completed.",
    "data": {
        "created_count": 2,
        "duplicate_count": 1,
        "created_barcodes": ["BAG-000034", "BAG-000035"],
        "duplicate_barcodes": ["BAG-000033"]
    }
}
```

## Update a barcode

Only available (unassigned) bags can be edited.

```http
PUT /api/seller/bags/33
Content-Type: application/json
Accept: application/json
```

```json
{
    "barcode": "BAG-REPLACEMENT-033"
}
```

Success (`200`):

```json
{
    "success": true,
    "message": "Bag updated successfully.",
    "data": {
        "id": 33,
        "barcode": "BAG-REPLACEMENT-033",
        "status": "available",
        "seller_order_id": null,
        "order_number": null,
        "assigned_at": null,
        "created_at": "2026-10-01T09:00:00.000000Z"
    }
}
```

A duplicate barcode returns `422`; an assigned bag returns `409`.

## Delete an available bag

```http
DELETE /api/seller/bags/33
Accept: application/json
```

Success (`200`):

```json
{
    "success": true,
    "message": "Bag deleted successfully.",
    "data": {
        "id": 33
    }
}
```

Assigned bags return `409` and cannot be deleted. Unknown or non-owned bag IDs return `404`.

## Admin visibility

The admin panel bag inventory uses the authenticated admin web routes:

```http
GET /admin/bags
GET /admin/bags/data?status=assigned&search=BAG-&page=1&per_page=25
Accept: application/json
```

The data response uses the same pagination shape as `GET /api/seller/bags`, and each item also includes `seller: { id, name }`. Admins can see available and assigned bag barcodes across sellers and the associated seller order number.
