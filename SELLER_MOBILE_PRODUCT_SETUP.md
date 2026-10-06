# Seller Mobile App: Product Setup and Unit Pricing

This document describes the product create/edit behavior and API contract the seller mobile app should follow. The mobile app must use the authenticated seller API; the existing web form routes are session-based and are not the mobile contract.

## Endpoints and Authentication

All endpoints are under `/api` and require the seller's Sanctum token:

```http
Authorization: Bearer <seller-token>
Accept: application/json
```

- `GET /api/seller/products/{id}`: load product data for edit, including variants, attributes, and store pricing.
- `POST /api/seller/products`: create a product.
- `POST /api/seller/products/{id}`: update a product. This endpoint uses POST, not PUT/PATCH.
- `GET /api/seller/products`: product list.
- `GET /api/seller/stores`: list the seller's stores for pricing rows.
- `GET /api/seller/attributes` and `/api/seller/attribute-values`: load attributes and values for variant configuration.

Create/update are `multipart/form-data` because images are uploaded with the product. Let the networking library generate the multipart boundary; do not set `Content-Type` manually if that prevents boundary generation.

## Product Form UX

Keep shipping weight distinct from sellable package contents:

- `weight` is shipping weight in kilograms and is used for delivery calculations.
- `net_quantity` and `net_quantity_unit` describe what the buyer receives, for example `200` + `g`, `1` + `L`, `5` + `pcs`, or `1` + `dozen`.
- `unit_count` and `unit_count_type` describe how many identical inner units are included in the sellable package, for example `2` + `packet` means two 500 g packets when `net_quantity=500` and `net_quantity_unit=g`. This is package composition, not the customer's order quantity.
- `net_quantity` is the contents of one inner unit when `unit_count` is set; total comparable contents are `net_quantity * unit_count`. When count is omitted, the effective count is one.
- Simple products enter the package amount and unit directly. Unit is free text (max 30 characters), not a fixed dropdown.
- Variant products use the selected Size, Weight, Volume, Quantity, or Pack attribute value as the package amount. Use values such as `200 g`, `1 L`, `5 pcs`, or `1 dozen`. Do not ask for a second editable package amount for variants; show the parsed amount as a read-only summary. Persist parsed variant package data as `net_quantity` and `net_quantity_unit` inside `variants_json`.
- Attribute names must contain one of `size`, `weight`, `volume`, `quantity`, or `pack` for package-size extraction. Attribute values must start with a number followed by a unit, e.g. `1 L`. Arbitrary units are supported if the package unit and comparison unit match.
- The unit-price basis is per store/variant pricing row. Default basis: `100 g` for g/kg, `100 ml` for ml/L, and `1 piece` for piece/dozen. For other units, default to `1 <same unit>`. Let sellers edit both the basis amount and unit, e.g. `1 g`, `100 g`, `1 ml`, `1 pc`, `1 dozen`, or `1 bottle`.
- Displayed unit price is calculated as the effective selling price divided by the number of comparison units in the package. Effective price is the special price when a special price is set; otherwise it is the regular price. Editing unit price updates the special price when one is set, and the regular price otherwise.
- Normalize common aliases for comparisons: `gm`/`gram` to `g`, `kilogram` to `kg`, `liter`/`litre` to `L`, `milliliter`/`millilitre` to `ml`, `pc`/`pcs`/`piece` to pieces, and `dozen` to 12 pieces. Do not convert unrelated custom units (e.g. bottle to ml).
- The API does not accept a separate `unit_price` field. Submit the resulting pack `price`/`special_price` and the chosen `unit_price_basis_quantity`/`unit_price_basis_unit`.

Formula:

```text
total package quantity = quantity per inner unit * unit_count (or quantity per pack when count is omitted)
comparable package quantity = total package quantity converted into the comparison unit
unit price = effective selling price / comparable package quantity * comparison amount
```

Examples:

- Pack `1 kg`, price ₹200, basis `100 g` -> ₹20 / 100 g.
- Pack `1 kg`, special price ₹150, basis `100 g` -> ₹15 / 100 g.
- Pack `5 pcs`, price ₹100, basis `1 pc` -> ₹20 / pc.
- Pack `1 dozen`, price ₹240, basis `1 piece` -> ₹20 / piece.
- Pack `500 g` x `2 packet`, price ₹200, basis `100 g` -> ₹20 / 100 g.
- Pack `1 bottle`, price ₹80, basis `1 bottle` -> ₹80 / bottle. A bottle cannot be compared to ml unless the package is entered as a volume such as `500 ml`.

## Create/Update Request Fields

Send the fields below as multipart form fields. Fields marked JSON must be serialized to a JSON string before appending to `FormData`.

| Field                                                                                                                        | Type                   | Requirement / notes                                                                                                                            |
| ---------------------------------------------------------------------------------------------------------------------------- | ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `title`                                                                                                                      | string                 | Required, max 255 characters.                                                                                                                  |
| `category_id`                                                                                                                | integer                | Required, must exist.                                                                                                                          |
| `brand_id`                                                                                                                   | integer/null           | Optional.                                                                                                                                      |
| `type`                                                                                                                       | string                 | Required: `simple` or `variant`.                                                                                                               |
| `base_prep_time`                                                                                                             | integer                | Required, minimum 0.                                                                                                                           |
| `short_description`                                                                                                          | string                 | Required, max 255 characters.                                                                                                                  |
| `description`                                                                                                                | string                 | Required.                                                                                                                                      |
| `image_fit`                                                                                                                  | string                 | Required: `cover` or `contain`.                                                                                                                |
| `minimum_order_quantity`                                                                                                     | integer                | Required, minimum 1.                                                                                                                           |
| `quantity_step_size`                                                                                                         | integer                | Required, minimum 1 and must be >= minimum order quantity.                                                                                     |
| `total_allowed_quantity`                                                                                                     | integer                | Required, minimum 0; 0 means unlimited.                                                                                                        |
| `main_image`                                                                                                                 | file                   | Required on create and update by current request validation; jpg/jpeg/png/webp, max 10 MB.                                                     |
| `additional_images[]`                                                                                                        | file(s)                | Optional; jpg/jpeg/png/webp, max 10 MB each.                                                                                                   |
| `pricing`                                                                                                                    | JSON string            | Required; shape shown below.                                                                                                                   |
| `variants_json`                                                                                                              | JSON string            | Required only for `type=variant`.                                                                                                              |
| `net_quantity`                                                                                                               | decimal                | Optional top-level field for simple products; if provided, must be >= 0.001.                                                                   |
| `net_quantity_unit`                                                                                                          | string                 | Required when simple `net_quantity` is supplied; max 30 characters.                                                                            |
| `unit_count`                                                                                                                 | integer                | Optional positive count of identical inner units in the sellable package.                                                                      |
| `unit_count_type`                                                                                                            | string                 | Required with `unit_count`; max 30 characters, e.g. `packet`, `bottle`, or `sachet`.                                                           |
| `weight`, `height`, `length`, `breadth`                                                                                      | decimal                | Simple variant shipping dimensions; optional at request validation, but send them for delivery calculations. Weight is kg; dimensions are cm.  |
| `barcode`                                                                                                                    | string                 | Required for simple products. Variant barcodes go inside each variant object.                                                                  |
| `tax_groups[]`                                                                                                               | repeated integer       | Optional; append one field per selected tax group ID. Do not JSON-encode this multipart array.                                                 |
| `is_inclusive_tax`                                                                                                           | boolean                | Optional; send `0` or `1`.                                                                                                                     |
| `tags[]`                                                                                                                     | string(s)              | Optional.                                                                                                                                      |
| `custom_fields[key]`                                                                                                         | string                 | Optional dynamic product fields; values max 255 characters.                                                                                    |
| `custom_sections_json`                                                                                                       | JSON string            | Optional custom product sections, if used by the app.                                                                                          |
| `video_type`, `video_link`                                                                                                   | string/URL             | Optional; video link must be a valid URL when present.                                                                                         |
| `product_video`                                                                                                              | file                   | Optional video upload; mp4/mov/avi, max 20 MB.                                                                                                 |
| `is_returnable`, `returnable_days`, `is_cancelable`, `cancelable_till`, `is_attachment_required`, `featured`, `requires_otp` | boolean/integer/string | Optional product settings. `returnable_days` is required when returnable is enabled; `cancelable_till` is required when cancelable is enabled. |

For both product types, `pricing` is one of these forms:

```json
{
    "store_pricing": [
        {
            "store_id": 12,
            "price": 200.0,
            "special_price": 150.0,
            "wholesale_price": null,
            "unit_price_basis_quantity": 100,
            "unit_price_basis_unit": "g",
            "cost": 100.0,
            "stock": 25,
            "sku": "SOAP-5PC"
        }
    ]
}
```

```json
{
    "variant_pricing": [
        {
            "variant_id": "variant-local-1",
            "store_id": 12,
            "price": 200.0,
            "special_price": 150.0,
            "wholesale_price": null,
            "unit_price_basis_quantity": 100,
            "unit_price_basis_unit": "g",
            "cost": 100.0,
            "stock": 25,
            "sku": "SOAP-5PC"
        }
    ]
}
```

Every pricing row requires `store_id`, numeric `price`, numeric `cost`, non-negative `stock`, and non-empty `sku`. The store must belong to the authenticated seller. `special_price` may be blank/null; when set, it must be non-negative and strictly less than regular `price`. `wholesale_price` is optional. Unit basis fields are optional for backward compatibility, but mobile should send both when displaying unit pricing. Basis quantity must be > 0 and basis unit max 30 characters.

## Simple Product Example

The example below is the conceptual multipart payload. `pricing` is a string containing the shown JSON; `main_image` is an actual image file.

```text
title=Herbal Soap Pack
category_id=41
brand_id=8
type=simple
base_prep_time=0
short_description=Five handmade soap bars
description=Pack of five 100 g soap bars.
image_fit=contain
minimum_order_quantity=1
quantity_step_size=1
total_allowed_quantity=0
barcode=8901234567890
weight=0.55
height=8
length=12
breadth=6
net_quantity=500
net_quantity_unit=g
unit_count=2
unit_count_type=packet
is_inclusive_tax=1
main_image=<file>
pricing={"store_pricing":[{"store_id":12,"price":200,"special_price":150,"wholesale_price":null,"unit_price_basis_quantity":100,"unit_price_basis_unit":"g","cost":100,"stock":25,"sku":"MASAALA-500G-2PK"}]}
```

This example represents 500 g per packet, 2 packets per sellable pack, or 1 kg total contents. Unit price basis can be `100` + `g` to show price per 100 g. Use `net_quantity=1`, `net_quantity_unit=dozen` for a dozen pack; leave `unit_count` absent unless there are multiple dozens in the package.

## Variant Product Example

`variants_json` is a JSON string. Attribute/value IDs come from the seller attribute APIs; send IDs, not display names. The same client-generated variant ID must appear in the matching `pricing.variant_pricing[].variant_id` row.

```text
type=variant
variants_json=[{"id":"variant-local-1","title":"2 x 500 g packets","attributes":[{"attribute_id":7,"value_id":42}],"barcode":"8901234567890","weight":1.1,"height":8,"length":12,"breadth":6,"availability":"yes","is_default":"on","net_quantity":500,"net_quantity_unit":"g","unit_count":2,"unit_count_type":"packet"},{"id":"variant-local-2","title":"4 x 500 g packets","attributes":[{"attribute_id":7,"value_id":43}],"barcode":"8901234567891","weight":2.2,"height":10,"length":18,"breadth":8,"availability":"yes","is_default":"off","net_quantity":500,"net_quantity_unit":"g","unit_count":4,"unit_count_type":"packet"}]
pricing={"variant_pricing":[{"variant_id":"variant-local-1","store_id":12,"price":200,"special_price":150,"wholesale_price":null,"unit_price_basis_quantity":100,"unit_price_basis_unit":"g","cost":100,"stock":25,"sku":"MASAALA-500G-2PK"},{"variant_id":"variant-local-2","store_id":12,"price":420,"special_price":null,"wholesale_price":null,"unit_price_basis_quantity":100,"unit_price_basis_unit":"g","cost":220,"stock":12,"sku":"MASAALA-500G-4PK"}]}
```

Each variant requires non-empty `title`, `attributes`, `barcode`, `weight`, `height`, `length`, and `breadth`; exactly one variant must have `is_default="on"`. `availability` should be `yes` or `no`. Variant image files, if uploaded, use the multipart key `variant_image<variant-id>` (e.g. `variant_imagevariant-local-1`). Variant `net_quantity` and `net_quantity_unit` may be omitted if the app is not providing package data. `unit_count` and `unit_count_type` are optional but must be sent together; `unit_count` must be a positive integer.

## Success Responses

All API responses use `{ "success": boolean, "message": string, "data": ... }`.

Create responds HTTP 201:

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "product_id": 246,
        "product_uuid": "4eb10abc-1234-4567-890a-1234567890ab"
    }
}
```

Update responds HTTP 200 with the same `data` structure.

## Edit Response Structure

Use `GET /api/seller/products/{id}` to load the product, including variant attributes, package size, and store pricing. Relevant response structure:

```json
{
    "success": true,
    "message": "Product fetched successfully",
    "data": {
        "id": 246,
        "uuid": "4eb10abc-1234-4567-890a-1234567890ab",
        "title": "Herbal Soap Pack",
        "category_id": 41,
        "brand_id": 8,
        "type": "simple",
        "short_description": "Five handmade soap bars",
        "description": "Pack contains two packets of 500 g each.",
        "image_fit": "contain",
        "minimum_order_quantity": 1,
        "quantity_step_size": 1,
        "total_allowed_quantity": 0,
        "is_inclusive_tax": true,
        "variants": [
            {
                "id": 903,
                "title": "Masala Pack",
                "weight": 1.1,
                "net_quantity": 500,
                "net_quantity_unit": "g",
                "unit_count": 2,
                "unit_count_type": "packet",
                "height": 8,
                "length": 12,
                "breadth": 6,
                "barcode": "8901234567890",
                "is_default": true,
                "attributes": {},
                "stores": [
                    {
                        "id": 8801,
                        "store_id": 12,
                        "store_name": "Nagpur Central",
                        "sku": "MASAALA-500G-2PK",
                        "price": 200,
                        "special_price": 150,
                        "wholesale_price": null,
                        "unit_price_basis_quantity": 100,
                        "unit_price_basis_unit": "g",
                        "cost": 100,
                        "stock": 25
                    }
                ]
            }
        ]
    }
}
```

The example is intentionally limited to product setup fields; the actual resource also returns category/brand display data, images, tags, tax classes, status, and other seller fields. For a simple product, `variants` contains one default variant, and package fields (`net_quantity`, `net_quantity_unit`, `unit_count`, and `unit_count_type`) live on that variant in the read response even though simple-product write fields are top-level.

## Error Handling

- `401`: missing/expired Sanctum token.
- `403`: seller lacks permission.
- `404`: product not found or not owned by this seller.
- `422`: request validation failure. Laravel FormRequest validation uses the standard JSON validation response, typically `{ "message": "The given data was invalid.", "errors": { "pricing": ["..."] } }`.
- `500`: unexpected server error, normally returned in the API response envelope.

Always surface field errors beside the corresponding wizard inputs. Keep entered form data if validation fails.

## Mobile Implementation Checklist

- [ ] Use bearer-token seller API routes, not web `/seller/products/...` routes.
- [ ] Build simple and variant package entry differently: direct amount/unit for simple, attribute-derived amount/unit for variants.
- [ ] Keep shipping weight separate from net package quantity.
- [ ] For multipacks, submit `unit_count` and `unit_count_type` together; `net_quantity` is the content quantity per inner unit (for example `500 g` x `2 packet`).
- [ ] Support custom package and comparison units up to 30 characters.
- [ ] Convert kg to g, L to ml, and dozen to 12 pieces; only compare custom units that match after trimming/case normalization.
- [ ] Calculate unit price from special price when set; otherwise regular price.
- [ ] Recalculate the effective pack price when a seller edits unit price, without changing the inactive regular/special price field.
- [ ] Send `unit_price_basis_quantity` and `unit_price_basis_unit` on each store pricing row so the comparison basis restores on edit.
- [ ] Submit `pricing` and `variants_json` as JSON strings within multipart data, not nested form keys.
- [ ] On edit, use the product detail response as the source for `variants`, `attributes`, `net_quantity`, `net_quantity_unit`, store rows, and saved unit-price basis.
