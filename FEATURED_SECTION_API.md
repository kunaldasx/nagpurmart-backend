# Featured Section API

Base URL: `https://<backend-host>/api`

## Get Homepage Sections

```http
GET /featured-sections
```

Query parameters:

- `per_page`: sections per page, default `15`, maximum `100`
- `page`: page number
- `section_type`: `newly_added`, `top_rated`, `best_seller`, `featured`, `best_price`, or `lowest_price`
- `products_limit`: products returned inside each section, default `10`, maximum `50`
- `scope_category_slug`: category slug for category-scoped sections
- `latitude`, `longitude`: optional coordinates for delivery-zone filtering; send both together

Example:

```http
GET /featured-sections?section_type=lowest_price&products_limit=10
```

Response data contains pagination fields and `data`, an array of sections. Each section includes `id`, `title`, `slug`, `section_type`, `scope_type`, `categories`, `products`, and `products_count`.

## Get One Section's Products

```http
GET /featured-sections/{section_slug}/products
```

Query parameters:

- `per_page`: products per page, default `15`, maximum `100`
- `page`: page number
- `sort`: `price_asc`, `price_desc`, `relevance`, `avg_rated`, `best_seller`, or `featured`
- `categories`: comma-separated category slugs
- `brands`: comma-separated brand slugs
- `attribute_values`: comma-separated global attribute-value IDs
- `latitude`, `longitude`: optional coordinates; send both together

Example:

```http
GET /featured-sections/lowest-price/products?per_page=20&sort=price_asc
```

The section's configured type is always applied. `sort` is an optional override for the product order.

## Get One Section

```http
GET /featured-sections/{section_slug}?products_limit=10
```

Optional query parameters: `products_limit`, `latitude`, and `longitude`.

## Section Type Behavior

- `newly_added`: newest products first
- `top_rated`: highest review rating first
- `best_seller`: most order items first
- `featured`: products marked as featured
- `lowest_price`: lowest available default-variant selling price first; a valid special price is preferred
- `best_price`: largest percentage discount on the default variant first

## Frontend Flow

1. Call `GET /featured-sections` for the homepage.
2. Render each section using its `style` and `section_type`.
3. Use the embedded `products` for the initial section display.
4. For a View All action, call `GET /featured-sections/{slug}/products`.
5. Use `scope_category_slug` when loading category-specific homepage sections.
6. Send `latitude` and `longitude` when product availability must be limited to the customer's delivery zone.

All responses use the standard API envelope:

```json
{
    "success": true,
    "message": "...",
    "data": {}
}
```
