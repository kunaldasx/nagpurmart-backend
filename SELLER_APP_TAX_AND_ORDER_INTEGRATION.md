# Seller app: tax setup and order billing integration

This guide covers seller-facing order details, buyer GSTIN, product tax setup, and store tax identity for the seller app. Use the existing seller APIs; the app must display backend-calculated tax and must not recalculate it.

## What the seller app needs to change

1. In seller order details, show the order's saved tax breakdown, taxable amount, HSN/SAC, buyer GSTIN (only when the buyer selected it for that order), and supplier tax identity.
2. Do not rely on the seller order list for tax details. The list is a compact operational response and currently omits them; fetch the order detail before rendering billing/tax information.
3. In product create/edit, expose tax group assignment, inclusive/exclusive pricing, and HSN/SAC if sellers are permitted to configure these fields by policy.
4. In store settings, show the store's tax identity (`tax_name` and `tax_number`) and allow authorized sellers to keep it current. These are seller/store registration details, not product tax rates.

The seller order detail response contains snapshots for new orders. Use those order-item snapshots for history and invoice/billing screens, not current product or store settings.

## Authentication and response envelope

Authenticated seller endpoints require the seller's existing Sanctum bearer token and seller approval middleware. Responses use the standard envelope:

```json
{
  "success": true,
  "message": "Localized response message",
  "data": {}
}
```

Handle non-success status codes and `success: false` as errors. Do not use the localized message as a programmatic code.

## Seller order details

### Endpoints

- List seller orders: `GET /api/seller/orders`
- Fetch seller order details: `GET /api/seller/orders/{seller_order_id}`

`seller_order_id` is the seller's order ID (`seller_orders.id`), not the global `orders.id`, order number, or UUID. The list response is paginated and intentionally compact; after the seller selects an order, fetch its detail endpoint for billing and tax fields.

### Relevant detail response

The response's `data.order` contains buyer/order information; `data.items[]` contains seller-order item records. A tax-bearing item looks like:

```json
{
  "id": 812,
  "order_id": 321,
  "seller_id": 44,
  "total_price": 249.0,
  "order": {
    "id": 321,
    "order_number": "NM-2026-00123",
    "uuid": "order-uuid",
    "payment_method": "cod",
    "order_mode": "regular",
    "billing_name": "Customer Name",
    "billing_phone": "9999999999",
    "billing_address_1": "42 Example Street",
    "billing_address_2": null,
    "billing_city": "Nagpur",
    "billing_state": "Maharashtra",
    "billing_zip": "440001",
    "billing_country": "India",
    "customer_gstin": "27AAPFU0939F1ZV",
    "customer_gstin_legal_name": "Example Trading Pvt Ltd",
    "customer_gstin_state_code": "27"
  },
  "items": [
    {
      "id": 901,
      "seller_order_id": 812,
      "order_item_id": 456,
      "quantity": 1,
      "price": 237.14,
      "subtotal": 249.0,
      "tax_percent": 5.0,
      "tax_amount": 11.86,
      "tax_total": 11.86,
      "taxable_subtotal": 237.14,
      "unit_price_with_tax": 249.0,
      "is_tax_inclusive": true,
      "tax_breakdown": [
        {
          "tax_group": "GST",
          "name": "GST 5%",
          "rate": 5.0,
          "unit_amount": 11.86,
          "amount": 11.86
        }
      ],
      "hsn_code": "123456",
      "supplier_tax_name": "GSTIN",
      "supplier_tax_number": "27ABCDE1234F1Z5",
      "product": {
        "id": 101,
        "title": "Example product"
      }
    }
  ]
}
```

This is a relevant-field example, not the complete resource: the response also includes status, delivery, seller, variant, bag, and timestamp fields. Older orders may have null/empty tax snapshots because the snapshot fields were introduced later.

### How to render order billing

- Show the buyer GSTIN and registered name only if `data.order.customer_gstin` is non-null. The buyer opted to apply it to this particular order; a saved customer GSTIN is not automatically used.
- Use `data.order.billing_*` for billing and the existing `shipping_*` fields for delivery. Avoid silently substituting one for the other.
- Show each `tax_breakdown[]` entry's group/name, percentage, and line amount. A component amount can be zero after line-level currency rounding; do not omit a configured component solely for that reason.
- `tax_amount` is a legacy per-unit value. `tax_total` and `tax_breakdown[].amount` represent the whole order line.
- `subtotal` is the saved line total, already including product tax. `taxable_subtotal` is the taxable base. `unit_price_with_tax` is the unit payable price.
- If `is_tax_inclusive` is true, label the tax as included in the item price. If false, label it as added to the taxable value. Do not add `tax_total` to `subtotal` again.
- Use saved `hsn_code`, `supplier_tax_name`, and `supplier_tax_number` for the order; do not replace these with current product/store values.
- This seller order detail API does not expose a seller-specific invoice URL. Do not construct one from an order ID. Use the designated invoice workflow supplied by the platform if the seller app provides invoice access.

## Product tax setup

Tax groups and their rates are configured centrally and are read-only to sellers in the seller API. Sellers may read the available classes and rates:

- `GET /api/seller/tax-classes`
- `GET /api/seller/tax-classes/{tax_class_id}`

The list response is paginated in `data.data`; each class contains its `title` and related `tax_rates` with their titles and rates. These endpoints require seller authentication and the seller's tax-class view permission.

Seller product create/edit endpoints:

- `POST /api/seller/products`
- `POST /api/seller/products/{product_id}`
- Read product detail: `GET /api/seller/products/{product_id}`

The product create/update request is multipart/form-data and has other required fields (including product description, category/type, pricing JSON, and a main image). Include the following tax-related fields in that existing request:

| Field | Type | Meaning |
|---|---|---|
| `tax_groups` | array of integer tax-class IDs | Product's assigned groups. IDs correspond to the globally configured classes. |
| `is_inclusive_tax` | boolean | Whether the entered product price already contains tax. |
| `hsn_code` | string or null | Product HSN/SAC code to snapshot onto future order items. |

Example request fields (shown as JSON for clarity; use multipart fields in the actual product request):

```json
{
  "tax_groups": [3],
  "is_inclusive_tax": true,
  "hsn_code": "123456"
}
```

The product detail resource returns these as `tax_classes`, `is_inclusive_tax`, and `hsn_code`. `tax_classes` includes configured rate data. The `hsn_code` field is returned by the seller product detail resource so an edit form can prepopulate it.

Rates across all groups assigned to a product are summed. They are cumulative components, not alternative tax slabs. Assign only the intended group(s); do not attach multiple rate slabs as a way to express choices.

## Store tax identity

The store's `tax_name` and `tax_number` represent the seller/store's tax registration identity; they are separate from product tax groups/rates. Seller store detail and list responses include these fields:

- `GET /api/seller/stores`
- `GET /api/seller/stores/{store_id}`

To update a store, use `POST /api/seller/stores/{store_id}` with its existing update payload, including required store details and media fields. The update request validates `tax_name` as a required string (up to 255 characters) and `tax_number` as a required string (up to 50 characters). The app should use a clear label such as “Tax registration name” and “Tax registration number/GSTIN” rather than presenting these as rate settings.

Current store values may change over time. Order items snapshot the supplier's tax name and number when an order is placed, so use those snapshots for historical order and invoice displays.

## Compliance and configuration limitations

The app should present configured data accurately but should not claim the backend validates statutory GST treatment. Tax rates and groups are administrator-configured. The system does not verify GSTIN registration or legal-name ownership, infer tax rate from HSN/SAC, determine place of supply, or automatically choose CGST/SGST versus IGST. Have a tax professional verify the seller's registration, product HSN/SAC and rate, place-of-supply treatment, invoice numbering, and discount tax handling before relying on generated documents as statutory tax invoices.
