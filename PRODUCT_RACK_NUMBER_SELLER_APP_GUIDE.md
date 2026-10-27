# Seller App Product Rack Number Integration Guide

This guide describes how to implement product rack numbers in the seller app: create/edit input, product detail display, and rack-location display in the seller order popup while picking and scanning items.

## Feature behavior

- `rack_number` is an optional product-level location string, not a variant-level value.
- Maximum length is 100 characters.
- The API returns either a string or `null`.
- If an update request omits `rack_number`, the existing value is retained. Send `null` or an empty string (Laravel normalizes empty strings to `null`) to clear it.
- The backend does not validate or interpret the rack label; sellers can use their own aisle/shelf naming convention.

## Authentication and base URL

Prepend the deployment API base URL to each path. Product endpoints require the seller's Sanctum bearer token:

```http
Authorization: Bearer <seller_access_token>
Accept: application/json
Content-Type: application/json
```

For multipart product requests (for example, requests that upload images), send `rack_number` as a regular text form field and let the HTTP client set the multipart content type and boundary.

## Create or edit a product

The seller product endpoints are:

```text
POST /api/seller/products       Create a product
POST /api/seller/products/{id}  Update a product
```

Both use the standard seller product request validation. Include `rack_number` alongside the normal create/update fields:

```json
{
    "title": "Example Product",
    "rack_number": "A-12",
    "category_id": 4,
    "type": "simple"
}
```

The example shows the rack field and a few representative product fields only; send all other fields required by the existing product form/API contract. The same field is supported on simple and variant products, because the rack belongs to the product as a whole.

To leave the location unset, omit the field or send `null`:

```json
{
    "rack_number": null
}
```

When editing an existing product, omitting `rack_number` preserves its current value. To clear a saved location, send `null` (or an empty string, which the backend converts to `null`).

Successful create response (`201`):

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "product_id": 179,
        "product_uuid": "f96a70e4-9801-4f4a-98b2-0ae9b0a8f3d9"
    }
}
```

Successful update response (`200`):

```json
{
    "success": true,
    "message": "Product updated successfully",
    "data": {
        "product_id": 179,
        "product_uuid": "f96a70e4-9801-4f4a-98b2-0ae9b0a8f3d9"
    }
}
```

The exact `message` text is localized; use HTTP status and `success` rather than matching message text.

Rack validation failure returns HTTP `422` with Laravel's standard validation shape:

```json
{
    "message": "The rack number field must not be greater than 100 characters.",
    "errors": {
        "rack_number": [
            "The rack number field must not be greater than 100 characters."
        ]
    }
}
```

Show the `rack_number` field validation error beside the input. On other failures, follow the app's existing product API error handling.

## Read product details

Fetch the seller-owned product using:

```http
GET /api/seller/products/{id}
```

The successful response uses the standard API envelope. Include `rack_number` in the product detail model and use it to prefill the edit field:

```json
{
    "success": true,
    "message": "Product fetched successfully",
    "data": {
        "id": 179,
        "uuid": "f96a70e4-9801-4f4a-98b2-0ae9b0a8f3d9",
        "seller_id": 12,
        "title": "Example Product",
        "rack_number": "A-12",
        "type": "simple",
        "main_image": "https://example.com/product.jpg",
        "variants": []
    }
}
```

Other product properties are omitted from this example. Older products without an assigned rack return `"rack_number": null`; treat `null` and an empty value as “no rack location” and do not render an empty badge.

## Display on product details and edit screens

- Add an optional text input labeled **Rack number** to both create and edit product screens.
- Limit the input to 100 characters and submit the entered value as `rack_number`.
- On edit, initialize the field from the product detail's `rack_number`.
- On the product details screen, show a highlighted rack badge/text near the product name or summary only when a non-empty rack number is present.
- Display the stored value as text; do not assume it is numeric.

## Show rack location in the order popup

The assigned-order endpoint is:

```http
GET /api/seller/orders/pending-regular?order_mode=regular
```

Use `order_mode=wholesale` for wholesale orders (and `popup=1` when using the wholesale popup window). Each item now includes the product-level `rack_number`:

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
                "items": [
                    {
                        "order_item_id": 9901,
                        "product_id": 179,
                        "product": "Example Product",
                        "rack_number": "A-12",
                        "variant": "500 g",
                        "sku": "EXAMPLE-500G",
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

Show the rack location next to each product in the order summary and prominently during the item verification/scanning step to help the picker locate it. If `rack_number` is `null` or empty, omit the rack label entirely. The value is informational only: do not use it as an item identifier or alter barcode/quantity verification based on it.

## Compatibility and release checklist

- Keep `rack_number` nullable in the app's product model so existing catalog entries remain valid.
- Handle older API responses that do not contain the field the same way as `null`.
- Display and escape rack text as ordinary user-entered content; do not interpret it as HTML.
- Do not display expected item barcodes to the picker; rack-number support does not change the existing barcode verification flow.
- Apply the backend database migration before relying on this field in a deployed environment.

The full order popup lifecycle, endpoint list, and verification contract remain in [SELLER_ORDER_POPUP_FLOW_API.md](./SELLER_ORDER_POPUP_FLOW_API.md).
