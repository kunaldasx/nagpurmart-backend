# Catalog Product Requests API

Customer endpoints for requesting a product that is not yet in the catalog. Both endpoints require an authenticated user access token. Seller accounts can use these endpoints too.

## Authentication

Send the user's Sanctum bearer token:

```http
Authorization: Bearer <seller_token>
Accept: application/json
```

## Submit a request

`POST /api/user/product-requests`

Content type: `application/json`

| Field           | Type   | Required | Constraints                         |
| --------------- | ------ | -------: | ----------------------------------- |
| `product_name`  | string |      Yes | Maximum 255 characters              |
| `brand_name`    | string |       No | Maximum 255 characters              |
| `category_name` | string |       No | Maximum 255 characters              |
| `description`   | string |       No | Maximum 5,000 characters            |
| `reference_url` | URL    |       No | Valid URL, maximum 2,048 characters |
| `request_note`  | string |       No | Maximum 2,000 characters            |

Example request:

```json
{
    "product_name": "Organic Millet Pasta 500g",
    "brand_name": "Green Harvest",
    "category_name": "Pasta and Noodles",
    "description": "Gluten-free millet pasta, 500 g pack.",
    "reference_url": "https://example.com/products/millet-pasta",
    "request_note": "Customers have asked for this item."
}
```

Success response (`201 Created`):

```json
{
    "success": true,
    "message": "Product request submitted successfully.",
    "data": {
        "id": 41,
        "product_name": "Organic Millet Pasta 500g",
        "brand_name": "Green Harvest",
        "category_name": "Pasta and Noodles",
        "description": "Gluten-free millet pasta, 500 g pack.",
        "reference_url": "https://example.com/products/millet-pasta",
        "request_note": "Customers have asked for this item.",
        "status": "pending",
        "admin_remark": null,
        "created_at": "2026-10-11T10:30:00.000000Z",
        "processed_at": null
    }
}
```

An exact case-insensitive product name already in the catalog, or a matching pending/approved request, is rejected with `422 Unprocessable Entity`.

## List the seller's requests

`GET /api/user/product-requests?page=1&per_page=15`

`per_page` is optional and must be between 1 and 100. Results include only the authenticated user's requests, newest first.

Success response (`200 OK`):

```json
{
    "success": true,
    "message": "Product requests fetched successfully.",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "id": 41,
                "product_name": "Organic Millet Pasta 500g",
                "brand_name": "Green Harvest",
                "category_name": "Pasta and Noodles",
                "description": "Gluten-free millet pasta, 500 g pack.",
                "reference_url": "https://example.com/products/millet-pasta",
                "request_note": "Customers have asked for this item.",
                "status": "pending",
                "admin_remark": null,
                "created_at": "2026-10-11T10:30:00.000000Z",
                "processed_at": null
            }
        ]
    }
}
```

## Status and errors

`status` is one of `pending`, `approved`, or `rejected`. The admin panel updates the status and optional `admin_remark`; a request does not itself create a catalog product.

- `201`: request accepted
- `200`: list returned
- `401`: missing or invalid authentication
- `422`: validation error, catalog duplicate, or duplicate request

Validation failures are returned as JSON with the invalid fields and messages. The frontend should display field-level errors when available.
