# Product API

Base URL: `https://<backend-host>/api`

## Customer Product List

```http
GET /delivery-zone/products
```

Use the product listing endpoint with the available query parameters used by the app:

- `page`, `per_page`
- `search`
- `category_id`
- `brand_id`
- `store_id`
- `sort`: `price_asc`, `price_desc`, `relevance`, `avg_rated`, `best_seller`, `featured`
- `product_filter`: `featured`, `low_stock`, `out_of_stock`
- `latitude`, `longitude`: send both for delivery-zone filtering
- `price_drop`: `true` or `false`

Example:

```http
GET /delivery-zone/products?latitude=21.1458&longitude=79.0882&categories=electronics&sort=price_asc&per_page=20&price_drop=true
```

## Featured Price-Drop Products

Products with the new boolean field can be requested using the existing featured-section endpoint:

```http
GET /featured-sections/{section_slug}/products
```

Example:

```http
GET /featured-sections/lowest-price/products?per_page=20&sort=price_asc&price_drop=true
```

Each product object now includes:

```json
{
  "id": 123,
  "title": "Example product",
  "price_drop": true
}
```

`price_drop` is a boolean. Existing products default to `false`.

## Seller Product API

Seller endpoints require Sanctum authentication:

```http
GET /seller/products
GET /seller/products/{id}
POST /seller/products
POST /seller/products/{id}
```

Send:

```text
price_drop=true
```

or:

```text
price_drop=false
```

For multipart requests, send it as `1` or `0`.

The field is also returned by seller product resources.

## Standard Response

Responses use:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

The product list is usually in `data.data` when paginated.
