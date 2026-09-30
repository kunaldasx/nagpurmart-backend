# Product Barcode API

## Barcode rules

- A simple product has one default variant, and its barcode is entered on that variant.
- A variant product needs a barcode for each option combination. In the product editor, generate the variants and enter the barcode in each variant card.
- Barcodes are unique across all product variants, including simple products. Duplicate values are rejected during create/update and by a database unique index.

## Get a product by barcode

`GET /api/products/barcode/{barcode}`

This public endpoint requires no request body or query parameters. URL-encode the barcode path segment when it contains reserved URL characters.

Example:

```http
GET /api/products/barcode/8901234500021
Accept: application/json
```

Successful response (`200`):

```json
{
    "success": true,
    "message": "Product fetched successfully",
    "data": {
        "product": {
            "id": 123,
            "uuid": "product-uuid",
            "category_id": 12,
            "brand_id": 34,
            "seller_id": 56,
            "title": "Classic T-Shirt",
            "slug": "classic-t-shirt",
            "type": "simple",
            "short_description": "A timeless classic t-shirt.",
            "description": "High-quality cotton t-shirt suitable for everyday wear.",
            "category": "clothing",
            "brand": "example-brand",
            "item_count_in_cart": 0,
            "is_save_for_later": false,
            "category_name": "Clothing",
            "brand_name": "Example Brand",
            "seller": "Example Seller",
            "indicator": null,
            "label": null,
            "label_bg": null,
            "label_font_color": null,
            "favorite": null,
            "estimated_delivery_time": null,
            "base_prep_time": 10,
            "ratings": 0,
            "rating_count": 0,
            "main_image": "https://example.com/images/classic-t-shirt.jpg",
            "image_fit": "cover",
            "additional_images": [],
            "minimum_order_quantity": 1,
            "quantity_step_size": 1,
            "total_allowed_quantity": 0,
            "is_returnable": 1,
            "returnable_days": 7,
            "is_cancelable": 1,
            "cancelable_till": "preparing",
            "is_attachment_required": 0,
            "requires_otp": 0,
            "tags": ["t-shirt", "cotton"],
            "custom_fields": [],
            "warranty_period": null,
            "guarantee_period": null,
            "made_in": "India",
            "is_inclusive_tax": false,
            "video_type": null,
            "video_link": null,
            "status": "active",
            "featured": false,
            "price_drop": false,
            "is_one_rupee_gift": false,
            "metadata": null,
            "created_at": "2026-09-30T10:00:00.000000Z",
            "updated_at": "2026-09-30T10:00:00.000000Z",
            "seller_ratings": {},
            "store_status": {
                "is_open": true,
                "status": "online"
            },
            "variants": [
                {
                    "id": 456,
                    "title": "Classic T-Shirt",
                    "slug": "classic-t-shirt",
                    "image": "https://example.com/images/classic-t-shirt-variant.jpg",
                    "weight": 0.25,
                    "height": 2,
                    "breadth": 25,
                    "length": 30,
                    "availability": true,
                    "cart_item": {
                        "exists": false,
                        "cart_item_id": null
                    },
                    "barcode": "8901234500021",
                    "is_default": true,
                    "price": 999,
                    "special_price": 899,
                    "wholesale_price": null,
                    "original_price": 999,
                    "original_special_price": null,
                    "special_price_ends_at": null,
                    "is_special_price_active": true,
                    "store_id": 78,
                    "store_slug": "example-store",
                    "store_name": "Example Store",
                    "stock": 100,
                    "sku": "TSHIRT-001",
                    "attributes": {}
                }
            ],
            "attributes": [],
            "custom_product_sections": []
        },
        "matched_variant": {
            "id": 456,
            "title": "Classic T-Shirt",
            "slug": "classic-t-shirt",
            "image": "https://example.com/images/classic-t-shirt-variant.jpg",
            "weight": 0.25,
            "height": 2,
            "breadth": 25,
            "length": 30,
            "availability": true,
            "cart_item": {
                "exists": false,
                "cart_item_id": null
            },
            "barcode": "8901234500021",
            "is_default": true,
            "price": 999,
            "special_price": 899,
            "wholesale_price": null,
            "original_price": 999,
            "original_special_price": null,
            "special_price_ends_at": null,
            "is_special_price_active": true,
            "store_id": 78,
            "store_slug": "example-store",
            "store_name": "Example Store",
            "stock": 100,
            "sku": "TSHIRT-001",
            "attributes": {}
        }
    }
}
```

The example shows all fields returned by the current resources. Values such as ratings, images, store details, custom sections, and variant attributes depend on the product and request context; nullable fields may be `null`, and collection fields may be empty. `product.variants` contains every variant, while `matched_variant` contains the variant whose barcode matched.

Price and stock are variant-level values. Use `data.matched_variant.price`, `special_price`, and `stock` for the scanned item. Currently, the endpoint does not accept a store or location and returns pricing from the first store-pricing record selected by the existing resource (ordered by stock), which may not be the price for a particular store.

Not found or inactive product (`404`):

```json
{
    "success": false,
    "message": "Product not found",
    "data": null
}
```

Barcode conflicts submitted while creating or updating a product return the standard Laravel validation response with status `422` and the conflicting values in the validation errors.
