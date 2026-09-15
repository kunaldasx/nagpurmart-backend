# Offer Banners API

## List published banners

```http
GET /api/offer-banners
```

Optional query parameters:

```text
scope_type=global|category
scope_id=<category_id>
position=top|carousel
```

The new design uses template code `T_9` and is named `Save Big Weekend`. Configure eight category items for the eight cards. The frontend should render the footer from `metadata.footer` and use each resolved category image for the card artwork.

Example response:

```json
{
    "success": true,
    "data": [
        {
            "id": 12,
            "title": "Save Big Weekend",
            "template_code": "T_9",
            "template": {
                "code": "T_9",
                "name": "Save Big Weekend",
                "preview_url": "https://admin.example.com/assets/templates/T_9.svg"
            },
            "background_color": "#1248ed",
            "font_color": "#ffffff",
            "position": "top",
            "scope_type": "global",
            "scope_id": null,
            "display_order": 1,
            "images": [],
            "metadata": {
                "footer": {
                    "title": "SELECT BANK CARD OFFERS: UP TO 10% DISCOUNTS"
                }
            },
            "items": [
                {
                    "id": 101,
                    "title": "90% OFF",
                    "subtitle": "Home & Kitchen",
                    "type": "category",
                    "item_id": 24,
                    "metadata": {},
                    "item": {
                        "id": 24,
                        "title": "Home & Kitchen",
                        "slug": "home-kitchen",
                        "image": "https://admin.example.com/storage/category.png"
                    }
                }
            ]
        }
    ]
}
```

## Admin create/update payload

```http
POST /admin/offer-banners
POST /admin/offer-banners/{id}
Content-Type: multipart/form-data
```

Required fields:

```text
title
template_code=T_9
background_color
font_color
position=top|carousel
scope_type=global|category
visibility_status=published|draft
```

For the new template, send eight `offer_items` entries:

```text
offer_items[0][title]=90% OFF
offer_items[0][subtitle]=Home & Kitchen
offer_items[0][item_type]=category
offer_items[0][item_id]=24
```

Optional template metadata is JSON:

```json
{
    "footer": {
        "title": "SELECT BANK CARD OFFERS: UP TO 10% DISCOUNTS"
    }
}
```

Submit it as the `metadata` form field. Uploaded banner artwork remains available through repeated `images[]` fields.
