# Featured Sections: Frontend Integration

## Required frontend change

Featured sections now contain their own curated product list. Render the products from each section's `products` array, in the order received.

**Do not use `categories`, `scope_id`, or `category_ids` to fetch or populate the products displayed inside a featured section.** Those fields are metadata/filter information and do not define its curated product list. In particular, stop making a category-products request for every featured section.

## Homepage request

```http
GET /api/featured-sections?per_page=15&products_limit=10
```

Optional query parameters:

- `page`, `per_page` — paginate sections (`per_page` defaults to 15; max 100)
- `products_limit` — max embedded products per section (defaults to 10; max 50)
- `section_type` — filter sections by type
- `scope_category_slug` — request category-scoped sections
- `latitude` and `longitude` — send together to filter product availability by delivery zone

Use the `products` included in each section for the homepage cards. Its array order is the admin-configured display order.

## Response shape

The API uses the standard `{ success, message, data }` envelope. For the list endpoint, `data` contains pagination and a `data` array of sections:

```json
{
    "success": true,
    "message": "labels.featured_sections_fetched_successfully",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "id": 12,
                "title": "Shopper Favorites",
                "slug": "shopper-favorites",
                "short_description": "Popular picks to browse",
                "style": "with_background",
                "section_type": "best_seller",
                "sort_order": 1,
                "status": "active",
                "scope_type": "global",
                "scope_id": null,
                "scope_category_slug": "",
                "scope_category_title": "",
                "categories": [],
                "products": [
                    {
                        "id": 501,
                        "title": "Product selected first",
                        "slug": "product-selected-first",
                        "short_description": "Product description",
                        "main_image": "https://example.com/product.jpg",
                        "category_id": 8,
                        "category": "grocery"
                    },
                    {
                        "id": 318,
                        "title": "Product selected second",
                        "slug": "product-selected-second",
                        "short_description": "Another product",
                        "main_image": "https://example.com/product-2.jpg",
                        "variants": []
                    }
                ],
                "products_count": 2
            }
        ]
    }
}
```

The example omits other product and section fields. Product entries use the standard product resource; use `main_image` for the image. When `variants` are included, their `price`, `special_price`, and `is_special_price_active` fields provide pricing.

## Other endpoints

Fetch one section, with the same embedded `products` structure:

```http
GET /api/featured-sections/{slug}?products_limit=10
```

Fetch a paginated product list for a section detail / “View all” screen:

```http
GET /api/featured-sections/{slug}/products?per_page=15&page=1
```

The products endpoint returns pagination under `data`, with product entries in `data.data`. Its `category_ids` and `brand_ids` fields describe available filters; they are **not** the featured section's product list.

Optional products-endpoint parameters include `sort`, `categories`, `brands`, `attribute_values`, `price_drop`, `latitude`, and `longitude`. Without an explicit `sort`, configured product order is retained for curated sections. An explicit `sort` overrides that order.

## Frontend rendering flow

1. Request `GET /api/featured-sections`.
2. For each section, render its metadata/style and map `section.products` directly to product cards. Do not fetch products using the section's categories.
3. Keep the product array order as returned; do not re-sort it client-side.
4. Only call `GET /api/featured-sections/{slug}/products` when opening a paginated “View all” screen.
5. Continue using `scope_category_slug` to filter category-scoped sections, and send both location coordinates together when required.
