# Highlighted Sections API

## List sections

`GET /api/highlighted-sections`

All filters are optional and are combined together when supplied:

| Parameter                     | Description                                                                                                                       |
| ----------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| `scope_type`                  | `global` or `category`                                                                                                            |
| `category_id`                 | Category scope ID; automatically limits results to category-scoped sections when `scope_type` is omitted                          |
| `scope_category_slug`         | Category slug; returns matching category sections and global sections                                                             |
| `template` or `template_code` | `trusted_brands`, `warm_and_cozy`, `curated_picks`, `rain_ready`, `spotlight`, `deals`, `image_products`, or `hot_sips_and_snack` |
| `slug`                        | Exact highlighted section slug                                                                                                    |
| `per_page`                    | Optional pagination size, from 1 to 50                                                                                            |

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
                "button_color": "#ffffff",
                "button_text_color": "#1259b5",
                "badge_color": "#f3b41b",
                "badge_text_color": "#111111",
                "deal_badge_color": "#e5484d",
                "deal_price_color": "#e5484d",
                "sort_order": 1,
                "status": "active",
                "is_bgimage": true,
                "background_images": [
                    "https://example.com/media/highlight-background-desktop.png",
                    "https://example.com/media/highlight-background-mobile.png"
                ],
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

The section object includes `button_color`, `button_text_color`, `badge_color`, `badge_text_color`, `deal_badge_color`, and `deal_price_color` as nullable color strings. Use the first pair for the button background and label text, the second pair for regular badge background and label text, and the final pair for deal badge background and deal price text. These fields are returned for every template; the frontend can use the fields required by the selected `template`.

Sections are returned in ascending `sort_order`, with `id` used as the tie-breaker. Items inside each section are also returned in ascending `sort_order`. Set the section-level `sort_order` to control the display order of sections; set `items[index][sort_order]` to control item order. If an item sort order is omitted, its request array index is used.

## Supporting metadata

- `GET /api/highlighted-sections/templates` returns `{ "code": "...", "name": "..." }` entries.
- `GET /api/highlighted-sections/item-types` returns `product`, `category`, and `brand`.

Item images are optional. They are uploaded as `items[index][image]` in the admin form and returned as the item-level `image` URL. Existing item images remain unchanged when an edit does not include a replacement image.

Background images are optional and are returned as the `background_images` URL array. Set `is_bgimage` to `true` when the frontend should use them. The admin form accepts multiple files as `background_images[]`; uploading new files replaces the existing background-image collection during an edit.

## Admin write endpoints

The authenticated admin endpoints use `multipart/form-data`:

- `POST /admin/highlighted-sections`
- `POST /admin/highlighted-sections/{id}`

Required fields are `title`, `template`, `scope_type`, and at least one `items[]` entry. Each item requires `item_type`, `item_id`, and `title`. Optional color fields are `background_color`, `font_color`, `button_color`, `button_text_color`, `badge_color`, `badge_text_color`, `deal_badge_color`, and `deal_price_color`. Optional background fields are `is_bgimage` (`0` or `1`) and repeated `background_images[]` image files (maximum 10 files, 10 MB each).

Successful create/update responses return the complete highlighted-section object in `data`, including `is_bgimage`, `background_images`, and `items`.
