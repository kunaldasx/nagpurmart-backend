# Wholesale purchase API

Wholesale checkout uses the same cart and order endpoints as regular checkout.

## Rules

- `order_mode` is `regular` (default) or `wholesale`.
- Regular mode uses the active special price, falling back to the regular price.
- Wholesale mode uses each store variant's `wholesale_price`.
- Wholesale merchandise subtotal must be at least `1500`. Delivery, handling, promotions, wallet balance, and payment method do not reduce this threshold.
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

The response `payment_summary` includes `order_mode`, `items_total`, `wholesale_minimum_amount`, and `wholesale_minimum_met`.

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

A wholesale order whose selected wholesale item subtotal is below `1500` is rejected with the existing minimum-cart error and no order is persisted:

```json
{
    "success": false,
    "message": "The minimum cart amount is 1500"
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

Variant products use the same field under `variant_pricing[store_id][variant_id]`. The product pricing endpoint returns `wholesale_price` for each store variant.
