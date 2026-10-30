# Customer app: tax, billing, and GSTIN integration

This document defines the customer app changes for cart bills, placed orders, customer GSTIN, and invoices.

## Important tax behavior

- Product tax is calculated from the tax rates attached to the product's assigned tax groups.
- Each assigned rate is treated as a component. Rates across assigned groups are added together; the app must not add another tax calculation on top of the API prices.
- **Exclusive price:** the configured unit price is the taxable value; product tax is added to it.
- **Inclusive price:** the configured unit price already contains tax; the API reports the extracted tax, and the payable price remains the configured price.
- Tax is rounded to two decimal places at the **order-line** level. Component amounts in `tax_breakdown` sum to `tax_total`.
- Cart `payment_summary.items_total` and placed-order `subtotal` already include product tax. `product_tax_total` / `tax_total` are informational amounts included in that subtotal; do not add them to the amount due again.
- The tax group/rate names and values on a placed order are snapshots. Use the order snapshot for order history and invoice screens, not current product settings.
- `tax_amount` is a per-unit compatibility field. Use `tax_total` for the whole line and each `tax_breakdown[].amount` for the line-level component amount.

Example at 5%:

| Price mode | Entered unit price | Taxable value | Tax | Unit payable |
|---|---:|---:|---:|---:|
| Exclusive | ₹100.00 | ₹100.00 | ₹5.00 | ₹105.00 |
| Inclusive | ₹105.00 | ₹100.00 | ₹5.00 | ₹105.00 |

For quantity 2, the tax total is ₹10.00 and the line total is ₹210.00 in either example.

## GSTIN workflow

The GSTIN is optional and is never applied to a new order without the customer's explicit `use_gstin: true` choice.

1. On cart/checkout entry, read `GET /api/user/profile/gstin`. GSTIN is deliberately not included in the generic user resource.
2. If a GSTIN is saved, prompt the customer for **each order**: use the saved GSTIN, do not use a GSTIN, or enter a different GSTIN.
3. Offer an edit action that calls `PUT /api/user/profile/gstin`. Offer a remove action that calls `DELETE /api/user/profile/gstin`.
4. On checkout, send `use_gstin: true` only after the customer selects the GSTIN option. For a saved GSTIN, omit `customer_gstin`; the API uses the saved value. For a different one, send it in `customer_gstin`.
5. To persist a different GSTIN, either save it through the profile endpoint or send `save_gstin: true` with the order. The latter saves it only after order creation succeeds. To use it only once, omit or set `save_gstin: false`.
6. If `use_gstin` is false or omitted, the order GSTIN fields are `null`, even if the profile has a saved GSTIN.

The backend validates the 15-character GSTIN format and normalizes it to uppercase. It does **not** check the GSTIN's checksum, registration status, legal name, or ownership against a government registry.

## GSTIN profile API

All endpoints below require the customer's existing Sanctum bearer token.

### Read saved GSTIN

`GET /api/user/profile/gstin`

Success response:

```json
{
  "success": true,
  "message": "Customer GSTIN fetched successfully.",
  "data": {
    "gstin": "27AAPFU0939F1ZV",
    "legal_name": "Example Trading Pvt Ltd",
    "is_saved": true
  }
}
```

When none is saved, `gstin` and `legal_name` are `null` and `is_saved` is `false`.

### Save or replace saved GSTIN

`PUT /api/user/profile/gstin`

Request:

```json
{
  "gstin": "27AAPFU0939F1ZV",
  "legal_name": "Example Trading Pvt Ltd"
}
```

`legal_name` is optional. `gstin` is required and must match the GSTIN format. On success, the response data has the same structure as the read response.

### Remove saved GSTIN

`DELETE /api/user/profile/gstin`

Success response data:

```json
{
  "gstin": null,
  "legal_name": null,
  "is_saved": false
}
```

Invalid input returns HTTP 422 with Laravel's standard validation error response.

## Cart response fields

`GET /api/cart` returns the existing `{ "success": true, "message": "...", "data": ... }` envelope. Each cart item's existing `variant` object includes these tax fields:

```json
{
  "variant": {
    "taxable_price": 237.14,
    "price_with_tax": 249.0,
    "tax_percent": 5.0,
    "tax_amount": 11.86,
    "tax_total": 11.86,
    "taxable_subtotal": 237.14,
    "subtotal_with_tax": 249.0,
    "tax_breakdown": [
      {
        "tax_group": "GST",
        "name": "GST 5%",
        "rate": 5.0,
        "unit_amount": 11.86,
        "amount": 11.86
      }
    ],
    "is_tax_inclusive": true
  }
}
```

The example is for quantity 1 and an inclusive ₹249 unit price. For quantity 2, `tax_total`, `taxable_subtotal`, `subtotal_with_tax`, and each breakdown `amount` are line totals; the `*_price` fields and `tax_amount` / `unit_amount` remain per-unit values.

The existing cart `payment_summary` also contains:

```json
{
  "items_total": 249.0,
  "product_tax_total": 11.86,
  "payable_amount": 249.0
}
```

Other fees, discounts, wallet use, and delivery fields are omitted above. The app should use the existing final payable amount; `product_tax_total` is already part of `items_total`.

## Place order request

Create an order with `POST /api/user/orders` using the existing authenticated checkout flow. The full request includes the existing checkout fields, payment-provider fields when applicable, and the optional GSTIN fields shown below:

```json
{
  "payment_type": "cod",
  "order_mode": "regular",
  "address_id": 123,
  "delivery_time_slot_id": null,
  "delivery_date": null,
  "promo_code": null,
  "gift_card": null,
  "rush_delivery": false,
  "use_wallet": false,
  "order_note": null,
  "redirect_url": null,
  "transaction_id": null,
  "razorpay_order_id": null,
  "razorpay_signature": null,
  "use_gstin": true,
  "customer_gstin": "27AAPFU0939F1ZV",
  "customer_gstin_legal_name": "Example Trading Pvt Ltd",
  "save_gstin": false
}
```

`payment_type` and `address_id` are required. The `transaction_id` field is required for Stripe, Razorpay, and Paystack; Razorpay also requires `razorpay_order_id` and `razorpay_signature`. Wholesale orders require `delivery_time_slot_id` and `delivery_date`. Send required product attachment fields as multipart form data when applicable. Do not send `null` payment-provider values if the selected payment method requires them.

The GSTIN-specific request fields are:

```json
{
  "use_gstin": true,
  "customer_gstin": "27AAPFU0939F1ZV",
  "customer_gstin_legal_name": "Example Trading Pvt Ltd",
  "save_gstin": false
}
```

Field behavior:

| Field | Type | Behavior |
|---|---|---|
| `use_gstin` | boolean, optional; defaults to false | Explicit per-order choice. If true, use the submitted GSTIN or the profile GSTIN. |
| `customer_gstin` | string, optional | Per-order GSTIN override. Must match the 15-character format. |
| `customer_gstin_legal_name` | string, optional | Registered business name to snapshot with the selected GSTIN. If omitted, the saved profile name (when using its GSTIN), then the account name is used. |
| `save_gstin` | boolean, optional; defaults to false | Saves an explicitly submitted GSTIN to the profile after the order succeeds. Requires `use_gstin: true` and `customer_gstin`. |

If `use_gstin` is true but neither a valid per-order GSTIN nor a saved GSTIN exists, the request fails validation with HTTP 422. Sending a different GSTIN does not replace the saved one unless `save_gstin` is true or the profile endpoint is called.

The successful order-create response uses the standard envelope:

```json
{
  "success": true,
  "message": "Order created successfully.",
  "data": {
    "id": 321,
    "order_number": "NM-2026-00123",
    "uuid": "order-uuid",
    "invoice": "https://example.test/order-invoice?id=order-uuid",
    "customer_gstin": "27AAPFU0939F1ZV",
    "customer_gstin_legal_name": "Example Trading Pvt Ltd",
    "customer_gstin_state_code": "27",
    "tax_total": 11.86,
    "items": [
      {
        "id": 456,
        "title": "Example product",
        "quantity": 1,
        "price": 237.14,
        "subtotal": 249.0,
        "tax_amount": 11.86,
        "tax_percent": 5.0,
        "tax_total": 11.86,
        "taxable_subtotal": 237.14,
        "unit_price_with_tax": 249.0,
        "is_tax_inclusive": true,
        "hsn_code": "123456",
        "tax_breakdown": [
          {
            "tax_group": "GST",
            "name": "GST 5%",
            "rate": 5.0,
            "unit_amount": 11.86,
            "amount": 11.86
          }
        ],
        "supplier_tax_name": "GSTIN",
        "supplier_tax_number": "27ABCDE1234F1Z5"
      }
    ]
  }
}
```

This is the complete response envelope and the complete tax/GSTIN item contract; unrelated existing order fields are omitted from this example. The exact message is localized and should not be used as a programmatic status code.

## Order response and invoice display

The order response includes the invoice URL, billing fields, and order item list. The following billing/tax fields are available in addition to the complete structure above:

```json
{
  "data": {
    "invoice": "https://example.test/order-invoice?id=<order-uuid>",
    "billing_name": "Customer Name",
    "billing_address_1": "42 Example Street",
    "billing_address_2": null,
    "billing_city": "Nagpur",
    "billing_state": "Maharashtra",
    "billing_zip": "440001",
    "billing_country": "India",
    "customer_gstin": "27AAPFU0939F1ZV",
    "customer_gstin_legal_name": "Example Trading Pvt Ltd",
    "customer_gstin_state_code": "27",
    "tax_total": 11.86,
    "items": [
      {
        "title": "Example product",
        "quantity": 1,
        "price": 237.14,
        "subtotal": 249.0,
        "tax_amount": 11.86,
        "tax_percent": 5.0,
        "tax_total": 11.86,
        "taxable_subtotal": 237.14,
        "unit_price_with_tax": 249.0,
        "is_tax_inclusive": true,
        "hsn_code": "123456",
        "tax_breakdown": [
          {
            "tax_group": "GST",
            "name": "GST 5%",
            "rate": 5.0,
            "unit_amount": 11.86,
            "amount": 11.86
          }
        ],
        "supplier_tax_name": "GSTIN",
        "supplier_tax_number": "27ABCDE1234F1Z5"
      }
    ]
  }
}
```

This shows the data shape; the live response has additional existing fields. For different order endpoints, item tax data may be at the order-item root or nested under `orderItem`; use the values from the order item resource. The invoice URL remains the authoritative invoice document; open it rather than reconstructing an invoice from current product records.

Frontend rendering requirements:

- Show **Taxable value**, each named tax component and rate, its line amount, and **Total (incl. tax)**.
- For inclusive prices, identify tax as included in the unit/line price; do not add it again.
- For exclusive prices, show tax added to the taxable price. The API's `subtotal`/line total already includes it.
- Show `tax_total` as a breakdown/summary only; never add it again to `items_total`, `subtotal`, or payable.
- In order details, use the order's saved `tax_breakdown`, `hsn_code`, and supplier registration snapshot. Do not fetch today's tax groups/rates to render a historical order.
- Show buyer GSTIN and registered name only when `customer_gstin` is non-null. Show supplier tax registration only when `supplier_tax_number` is present and not a placeholder.
- Use `billing_*` fields for billing details and shipping fields for delivery. Do not substitute the shipping address for the billing address.
- For GSTIN state code, the API exposes the first two GSTIN characters. Do not infer the applicable GST component split from that value in the app.

## Tax invoice scope and compliance notes

The backend applies the rates configured by an administrator. It does not derive rates from HSN/SAC, determine place of supply, decide whether CGST/SGST or IGST applies, or verify GSTIN registration. Configure rate components correctly for each applicable product and supplier; assign only the intended group(s), since rates in assigned groups are summed. Keep different product slabs in separate groups rather than selecting multiple alternative slabs for one product.

Store tax identity and product HSN are snapshotted for new orders so invoice history does not change when a store/product is edited. Older orders pre-dating these snapshots can have missing tax names, HSN, and buyer GSTIN.

Have the seller's tax professional confirm rate, HSN/SAC, supplier/recipient registration, place-of-supply, invoice numbering, and any discount treatment before using generated documents as statutory GST tax invoices. The backend's 15-character GSTIN shape check is not a registration verification.
