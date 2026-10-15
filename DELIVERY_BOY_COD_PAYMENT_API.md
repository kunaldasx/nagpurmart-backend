# Rider COD Payment API

Base URL: `https://admin.nagpurmart.in`

All endpoints below require the rider's Sanctum bearer token. Payment endpoints are restricted to an active, verified rider and an order assigned to that rider.

## Setup Requirements

- Enable Razorpay UPI QR payments for the merchant account. Razorpay may need to activate the `upi_qr` feature for the account.
- Keep using the existing Razorpay Key ID, Key Secret, and Webhook Secret configured in the admin payment settings. Do not put the Key Secret or Webhook Secret in the rider app.
- Set `RAZORPAY_COD_QR_ENABLED=true` in the backend environment and reload Laravel configuration.
- Confirm the admin system currency is configured as `INR`; newly created orders use the configured system currency.
- In the Razorpay Dashboard, configure the webhook URL as `https://admin.nagpurmart.in/api/webhook/razorpay`, use the same webhook secret as the backend payment setting, and subscribe to `qr_code.credited`.
- Optionally configure `UPI_MERCHANT_VPA` and `UPI_MERCHANT_NAME` to return the merchant UPI ID for display.

The QR is a Razorpay-created, single-use, fixed-amount UPI QR and expires after 30 minutes. UPI QR availability is limited to INR COD orders. Cash remains available when QR payments are disabled or unavailable.

## Rider App Flow

1. Fetch payment instructions when the rider opens the COD collection step.
2. If `accepted_methods` contains `upi`, call the create-QR endpoint. It creates a QR or reuses the current unexpired QR.
3. Display `razorpay_qr.image_url` as the payment QR and show the amount. The customer can scan it with a UPI app.
4. While the QR status is `pending`, poll payment instructions every 3 seconds. Stop polling on `completed`, `expired`, or when the rider leaves the payment step. Never treat the UPI app's success screen or a customer-provided reference as backend confirmation.
5. When `payment_status` becomes `completed`, show payment success and continue to delivery confirmation. Submit the delivery OTP if required by the product.
6. If the QR expires, call create-QR again to get a fresh code. For cash, collect the amount and use the cash delivery request below.

## 1. Get Payment Instructions and Status

```http
GET /api/delivery-boy/order-items/{orderItemId}/payment-instructions
Authorization: Bearer <rider_token>
Accept: application/json
```

Example before a QR has been created:

```json
{
    "success": true,
    "message": "Payment instructions fetched successfully.",
    "data": {
        "required": true,
        "payment_status": "pending",
        "amount": "1250.00",
        "currency": "INR",
        "accepted_methods": ["cash", "upi"],
        "upi_id": "nagpurmart@upi",
        "upi_id_auto_confirmation": false,
        "razorpay_qr": null,
        "payment_collection": null
    }
}
```

`upi_id` is optional. A direct transfer to that ID is not linked to this COD payment and will not be automatically confirmed. Only the Razorpay QR payment is automatically verified. If QR is disabled, unconfigured, or the order is not in INR, `accepted_methods` contains only `cash`.

After QR creation, `razorpay_qr` is returned with `id`, `image_url`, `status` (`pending`, `completed`, or `expired`), and `expires_at`. After the Razorpay webhook confirms payment, `payment_status` is `completed`, `required` is `false`, and `payment_collection` contains the verified method, provider reference, and confirmation time.

For a non-COD order or an already-paid COD order, `required` is `false`.

## 2. Create or Reuse a UPI QR

```http
POST /api/delivery-boy/order-items/{orderItemId}/payment-qr
Authorization: Bearer <rider_token>
Accept: application/json
```

No request body is required. The amount, order, and rider are derived and checked on the backend. Repeated calls return the same active QR instead of creating duplicate active codes.

Success (`200`) returns the payment instructions shape above, with `razorpay_qr` populated:

```json
{
    "success": true,
    "message": "Razorpay UPI QR created successfully.",
    "data": {
        "required": true,
        "payment_status": "pending",
        "amount": "1250.00",
        "currency": "INR",
        "accepted_methods": ["cash", "upi"],
        "upi_id": "nagpurmart@upi",
        "upi_id_auto_confirmation": false,
        "razorpay_qr": {
            "id": "qr_HMsVL8HOpbMcjU",
            "image_url": "https://rzp.io/i/BWcUVrLp",
            "status": "pending",
            "expires_at": "2026-10-15T13:00:00+00:00"
        },
        "payment_collection": null
    }
}
```

The app can render the returned `image_url` as an image. Do not generate a new QR locally from `upi_id` or the old `upi_qr_payload`; those approaches do not produce the Razorpay payment callback needed for automatic confirmation.

## 3. Poll for Payment Success

Poll the GET payment-instructions endpoint while `razorpay_qr.status` is `pending`. A completed response includes:

```json
{
    "required": false,
    "payment_status": "completed",
    "razorpay_qr": {
        "id": "qr_HMsVL8HOpbMcjU",
        "image_url": null,
        "status": "completed",
        "expires_at": "2026-10-15T13:00:00+00:00"
    },
    "payment_collection": {
        "status": "completed",
        "method": "upi",
        "transaction_reference": "pay_HO2fEpc9JeOQU5",
        "confirmed_at": "2026-10-15T12:42:11+00:00"
    }
}
```

The signed `qr_code.credited` webhook is the source of truth. It validates that Razorpay reports a captured payment and that the QR, COD order, INR currency, and exact amount match before updating payment status. The webhook does not mark the order item delivered.

## 4. Complete Delivery After Payment

After a QR payment is confirmed by the GET endpoint, mark delivery as usual. Do not include `payment_collection` for the already-confirmed UPI payment:

```http
PUT /api/delivery-boy/order-items/{orderItemId}/status
Authorization: Bearer <rider_token>
Content-Type: application/json
Accept: application/json
```

```json
{
    "status": "delivered",
    "otp": "123456"
}
```

Include `otp` only when delivery OTP is required for the product. Other transitions retain their existing request shape, for example `{ "status": "collected" }`.

For cash collection, the rider confirms the cash in the same delivery request:

```json
{
    "status": "delivered",
    "payment_collection": {
        "method": "cash"
    }
}
```

Cash collection and delivery status remain committed together, and cash is added to the rider's cash ledger. An unpaid order cannot be marked delivered by supplying `payment_collection.method: "upi"`; UPI must first be confirmed by the Razorpay webhook.

Success (`200`) returns the existing serialized `data.order_item` and `data.order` fields. COD responses also include `data.payment_collection` with the completed method, amount, currency, provider reference (for UPI), and confirmation time.

## Errors

- `404`: order item is not assigned to the authenticated rider or does not exist.
- `409`: trying to create a new QR for an order already paid.
- `422`: QR feature disabled, non-COD order, non-INR order, or attempting delivery before payment confirmation.
- `502`: Razorpay QR creation failed. The rider app should keep cash available and allow retry where appropriate.

An unpaid COD delivery attempt returns HTTP `422` and does not change the item status:

```json
{
    "success": false,
    "message": "Confirm COD payment before marking the order item delivered.",
    "data": {
        "payment_required": true,
        "order_id": 8801,
        "amount": "1250.00",
        "currency": "INR",
        "payment_instructions_url": "/api/delivery-boy/order-items/9901/payment-instructions"
    }
}
```
