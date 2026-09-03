# Wholesale purchase API

Wholesale checkout uses the same cart and order endpoints as regular checkout.

## Rules

- `order_mode` is `regular` (default) or `wholesale`.
- Regular mode uses the active special price, falling back to the regular price.
- Wholesale mode uses each store variant's `wholesale_price`.
- Wholesale merchandise subtotal must be at least the configured `wholesaleMinimumAmount` (default `1500`). Delivery, handling, promotions, wallet balance, and payment method do not reduce this threshold.
- Every order response includes `order_mode`; order items and invoices contain the selected price snapshot.

Set the host and token before running examples:

```bash
HOST=https://example.com
TOKEN=your-sanctum-token
```

## Cart preview

Regular pricing (backward-compatible default):

```bash
curl -G "$HOST/api/cart" \
  -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "address_id=1" \
  --data-urlencode "order_mode=regular"
```

Wholesale pricing preview:

```bash
curl -G "$HOST/api/cart" \
  -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "address_id=1" \
  --data-urlencode "order_mode=wholesale"
```

The response `payment_summary` includes `order_mode`, `items_total`, `gift_minimum_cart_amount`, `wholesale_minimum_amount`, and `wholesale_minimum_met`. Both thresholds are editable in Admin Settings under Cart & Inventory Settings.

## Create a regular order

Omit `order_mode` or send `regular`:

```bash
curl -X POST "$HOST/api/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "payment_type=cod" \
  -F "address_id=1" \
  -F "order_mode=regular" \
  -F "rush_delivery=false" \
  -F "use_wallet=false"
```

## Create a wholesale order

```bash
curl -X POST "$HOST/api/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "payment_type=cod" \
  -F "address_id=1" \
  -F "order_mode=wholesale" \
  -F "rush_delivery=false" \
  -F "use_wallet=false"
```

For Stripe, Razorpay, or Paystack, include the existing `transaction_id` field and the payment-specific fields required by that gateway. Add `promo_code`, `gift_card`, `order_note`, and attachment fields exactly as with regular checkout.

## Expected failure below minimum

A wholesale order whose selected wholesale item subtotal is below the configured wholesale minimum is rejected with the existing minimum-cart error and no order is persisted:

```json
{
    "success": false,
    "message": "The minimum cart amount is the configured wholesale minimum"
}
```

## Product pricing payload

Admin/seller product create and edit requests accept `wholesale_price` in each pricing row:

```json
{
    "store_pricing": [
        {
            "store_id": 1,
            "price": 100,
            "special_price": 90,
            "wholesale_price": 75,
            "cost": 50,
            "stock": 20,
            "sku": "SKU-001"
        }
    ]
}
```

Variant products use the same field under each variant pricing row, for example:

```json
{
    "variant_pricing": [
        {
            "variant_id": 12,
            "store_id": 1,
            "price": 100,
            "special_price": 90,
            "wholesale_price": 75,
            "cost": 50,
            "stock": 20,
            "sku": "SKU-001"
        }
    ]
}
```

The product pricing endpoint returns `wholesale_price` for each store variant.

## One-rupee gift endpoints

An administrator enables a product as a gift from the admin product details page. The admin form posts to `POST /admin/products/{productId}/gift-settings`. The shared gift qualifying amount is configured in Admin Settings as `giftMinimumCartAmount`; every enabled gift product uses that same amount. Normal product purchases are unaffected and continue to use the selected regular or wholesale price.

Fetch gift choices after adding qualifying products:

```bash
curl -G "$HOST/api/cart/gifts" \
  -H "Authorization: Bearer $TOKEN" \
  --data-urlencode "order_mode=regular"
```

For wholesale mode, request `order_mode=wholesale`. The response includes `eligible`, `qualifying_items_total`, `gift_minimum_cart_amount`, and each option's `product_variant_id`, `store_id`, and fixed gift `price` of `1.00`. Each option also includes `name`, `slug`, `short_description`, `description`, `main_image`, `additional_images`, `image_fit`, `variant_title`, `variant_slug`, `variant_image`, `variant_attributes`, `regular_price`, `special_price`, `wholesale_price`, `original_price`, `original_special_price`, `special_price_ends_at`, `is_special_price_active`, `sku`, `stock`, `category`, `brand`, and `store`.

Add the selected gift. The server ignores any client price and always creates a gift line at `1.00`:

```bash
curl -X POST "$HOST/api/cart/gifts/add" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "product_variant_id=12" \
  -F "store_id=1" \
  -F "order_mode=regular"
```

Only one gift can be selected per cart and its quantity is always one. Remove it through the existing `DELETE /api/cart/item/{cartItemId}` endpoint. The existing `POST /api/cart/add` endpoint always adds a normal-priced line, even when that product is marked as a gift.

The cart response marks gift lines with `is_gift: true`. Order responses, seller/delivery order APIs, and invoices expose the same marker and the persisted ₹1 item price.
