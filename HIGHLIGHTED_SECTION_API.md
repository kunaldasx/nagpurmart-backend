# Highlighted Sections API

## List sections

`GET /api/highlighted-sections`

All filters are optional and are combined together when supplied:

| Parameter                     | Description                                                                                              |
| ----------------------------- | -------------------------------------------------------------------------------------------------------- |
| `scope_type`                  | `global` or `category`                                                                                   |
| `category_id`                 | Category scope ID; automatically limits results to category-scoped sections when `scope_type` is omitted |
| `scope_category_slug`         | Category slug; returns matching category sections and global sections                                    |
| `template` or `template_code` | `trusted_brands`, `warm_and_cozy`, `curated_picks`, `rain_ready`, or `spotlight`                         |
| `slug`                        | Exact highlighted section slug                                                                           |
| `per_page`                    | Optional pagination size, from 1 to 50                                                                   |

Example:

`GET /api/highlighted-sections?scope_type=category&category_id=12&template_code=spotlight&slug=summer-picks`

Response:

```json
{
    "success": true,
    "message": "Highlighted sections fetched successfully.",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "id": 4,
                "title": "Summer Picks",
                "slug": "summer-picks",
                "subtitle": "Fresh choices for the season",
                "template": "spotlight",
                "scope_type": "category",
                "scope_id": 12,
                "scope_category": {
                    "id": 12,
                    "title": "Grocery",
                    "slug": "grocery"
                },
                "background_color": "#1259b5",
                "font_color": "#ffffff",
                "sort_order": 1,
                "status": "active",
                "items": [
                    {
                        "id": 21,
                        "item_type": "product",
                        "item_id": 88,
                        "title": "Maggi Rich Tomato Ketchup",
                        "subtitle": "Easy-pour family pack",
                        "image": "https://example.com/media/highlight.png",
                        "sort_order": 1,
                        "item": {
                            "id": 88,
                            "title": "Maggi Rich Tomato Ketchup",
                            "slug": "maggi-rich-tomato-ketchup"
                        }
                    }
                ],
                "created_at": "2026-09-09 12:00:00",
                "updated_at": "2026-09-09 12:00:00"
            }
        ]
    }
}
```

## Get one section

`GET /api/highlighted-sections/{slug}`

This returns the same section object directly in `data`, rather than the paginated list wrapper.

## Supporting metadata

- `GET /api/highlighted-sections/templates` returns `{ "code": "...", "name": "..." }` entries.
- `GET /api/highlighted-sections/item-types` returns `product`, `category`, and `brand`.

Item images are optional. They are uploaded as `items[index][image]` in the admin form and returned as the item-level `image` URL. Existing item images remain unchanged when an edit does not include a replacement image.
