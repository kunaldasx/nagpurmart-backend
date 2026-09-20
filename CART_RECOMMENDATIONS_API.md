# Cart Recommendations API

## Endpoint

`GET /api/cart/recommendations`

Optional query parameter:

- `products_limit`: number of products per section, from 1 to 50. Default: `20`.

Only sections with `status=active` are returned, ordered by `sort_order`.

## Response

```json
{
    "success": true,
    "message": "Cart recommendations fetched successfully.",
    "data": {
        "sections": [
            {
                "id": 1,
                "heading": "Frequently bought together",
                "is_tabular": false,
                "sort_order": 1,
                "status": "active",
                "products": [
                    {
                        "id": 42,
                        "title": "Example product",
                        "slug": "example-product",
                        "main_image": "https://example.test/storage/product.jpg",
                        "status": "active",
                        "variants": []
                    }
                ]
            }
        ]
    }
}
```

In Flutter, call this endpoint when the cart screen loads, decode `data.sections`, and render each section using `heading` and `is_tabular`. Use `products` as the ordered product list. A tabular section can use a grid/table-style widget; a non-tabular section can use a horizontal list.
