# Product expiry batches

Expiry is tracked per store product variant and delivery batch. This lets the
same product have multiple lots with different expiry dates. Existing stock is
left unchanged and remains untracked until it is assigned to a batch.

Tracked quantities are reduced in earliest-expiry-first order when stock is
sold. If an order is cancelled or returned, its quantities are restored to the
same batches where possible. Expired and near-expiry stock raises alerts; it is
not automatically removed from total stock or made unsellable.

## Seller endpoints

All endpoints require the authenticated seller account and seller-user approval.

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/seller/inventory/expiry-batches` | List batches |
| GET | `/api/seller/inventory/expiry-batches/summary` | Counts and units at risk |
| POST | `/api/seller/inventory/expiry-batches` | Record a stock batch |
| POST | `/api/seller/inventory/expiry-batches/{batchId}` | Update a batch |

## Admin endpoints

These endpoints use the authenticated admin panel session.

| Method | Path | Purpose |
|---|---|---|
| GET | `/admin/inventory/expiry-batches` | List batches across sellers |
| GET | `/admin/inventory/expiry-batches/summary` | Counts and units at risk |
| POST | `/admin/inventory/expiry-batches` | Record a stock batch |
| POST | `/admin/inventory/expiry-batches/{batchId}` | Update a batch |

Admins can filter by `seller_id`. Both roles can filter listings with
`store_id`, `product_id`, `status`, `days`, `page`, and `per_page`.

`status` is `all` (default), `expired`, `expiring`, `safe`, or `depleted`.
`expiring` includes today through the `days` cutoff; the default warning window
is 15 days. Expired means the expiry date is before today. The summary response
includes batch counts and remaining-unit totals for expired and expiring stock.

## Create a batch

```json
{
  "store_product_variant_id": 321,
  "batch_code": "LOT-2026-10-A",
  "quantity": 20,
  "expiry_date": "2026-12-15",
  "adjust_stock": true
}
```

With `adjust_stock: true` (default), this records a new delivery and adds its
quantity to the store's existing stock. Use `adjust_stock: false` to assign a
batch expiry date to stock already on hand; the quantity must be available as
untracked stock and the total stock will not change.

## Update a batch

```json
{
  "expiry_date": "2026-12-20",
  "quantity_remaining": 12,
  "adjust_stock": true
}
```

Only provided fields are updated. `quantity_remaining` cannot exceed the
received quantity less units still sold and not yet restored. With
`adjust_stock: true` (default), changing the remaining quantity also adjusts
total store stock. With `false`, the quantity is reassigned between tracked
and untracked stock without changing the total. Set the remaining quantity to
zero to mark the batch depleted; batch records are retained for traceability.

## Alerts

The seller and admin notification feeds receive a database notification once
when a batch enters the 15-day near-expiry window and again when it becomes
expired. The `product-expiry:notify` command is scheduled daily at 08:00 and
can also be run manually.

Existing product stock is not assigned an expiry date by migration. Sellers or
admins should register each existing lot with `adjust_stock: false` and record
new deliveries with `adjust_stock: true`.

For example, if the existing stock is 10 units, register that lot with
`quantity: 10` and `adjust_stock: false`. When 20 units arrive with a different
expiry date, register a second batch with `quantity: 20` and
`adjust_stock: true`.
