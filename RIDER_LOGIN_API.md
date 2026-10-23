# Rider App Login API

These endpoints implement rider login in the delivery app. The platform admin configures the password approval duration and maximum QR lifetime under **Admin → Settings → Delivery Boy** (`riderLoginDurationHours`, 1–720 hours; default 24). Sellers may choose a shorter QR lifetime per rider.

Base URL examples use `https://api.example.com`. Send `Accept: application/json`; JSON requests use `Content-Type: application/json`.

## 1. Email and password login

`POST /api/delivery-boy/login` is public and rate-limited to 10 attempts per minute.

Request:

```json
{
  "email": "rider@example.com",
  "password": "rider-password",
  "fcm_token": "optional-device-fcm-token",
  "device_type": "android"
}
```

`email` and `password` are required. `fcm_token` and `device_type` are optional.

### Seller-linked rider approved

HTTP `200`:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "access_token": "42|sanctum-personal-access-token",
    "token_type": "Bearer",
    "expires_at": "2026-10-24T08:00:00.000000Z",
    "user": {
      "id": 42,
      "name": "Rider Name",
      "email": "rider@example.com",
      "mobile": "9876543210",
      "profile_image": null
    },
    "delivery_boy": {
      "id": 17,
      "user_id": 42,
      "seller_id": 5,
      "full_name": "Rider Name",
      "status": "active",
      "verification_status": "verified",
      "login_approval_status": "approved",
      "login_approved_until": "2026-10-24T08:00:00.000000Z"
    }
  }
}
```

The response resources may include additional profile fields. Keep the returned access token securely and send it as a bearer token on protected requests:

```http
Authorization: Bearer 42|sanctum-personal-access-token
Accept: application/json
```

The token expires at the seller approval deadline. At expiry the API rejects it; clear the saved token and show the login screen.

### Approval pending or expired

The first password login attempt for a seller-linked rider without a current grant creates a pending request and notifies the seller owner. HTTP `403`, with no access token:

```json
{
  "success": false,
  "message": "Your rider login request is waiting for seller approval.",
  "data": {
    "login_approval_status": "pending",
    "approval_requested_at": "2026-10-23T20:00:00.000000Z"
  }
}
```

Show a pending screen. The app can retry the same login request after the rider asks the seller to approve; do not persist the password to poll. When the seller approves, a retry returns the normal `200` response and token. The seller app can also expose its pending rider list using the seller endpoints in [SELLER_DELIVERY_BOY_LOGIN_API.md](./SELLER_DELIVERY_BOY_LOGIN_API.md).

### Other password login results

- Invalid credentials: HTTP `401`, `success: false`, `data: []`.
- Not a delivery boy, or a rider not verified by the platform: HTTP `403`.
- Request validation errors: HTTP `422` with Laravel's validation response.
- Seller approval applies to riders linked to a seller. Platform-managed riders without `seller_id` retain their existing password-login behavior until an admin assigns them to a seller.

## 2. QR login

The seller app displays the current QR credential for a verified rider. The QR contents are the opaque `qr_payload` string, not a URL. Scan it and send the exact string as `qr_token`.

`POST /api/delivery-boy/login/qr` is public and rate-limited to 10 attempts per minute.

Request:

```json
{
  "qr_token": "opaque-64-character-random-rider-credential",
  "fcm_token": "optional-device-fcm-token",
  "device_type": "android"
}
```

HTTP `200`:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "access_token": "42|sanctum-personal-access-token",
    "token_type": "Bearer",
    "expires_at": "2026-10-24T08:00:00.000000Z",
    "user": {
      "id": 42,
      "name": "Rider Name",
      "email": "rider@example.com",
      "mobile": "9876543210",
      "profile_image": null
    },
    "delivery_boy": {
      "id": 17,
      "user_id": 42,
      "seller_id": 5,
      "full_name": "Rider Name",
      "status": "active",
      "verification_status": "verified"
    }
  }
}
```

QR-issued rider tokens expire at the QR's `expires_at`. The seller can set an individual QR lifetime from 1 hour up to the platform-configured maximum when creating or rotating it. An invalid, revoked, or expired QR returns HTTP `401`:

```json
{
  "success": false,
  "message": "The rider QR code is invalid or expired.",
  "data": {
    "code": "invalid_or_expired_qr"
  }
}
```

Seller QR retrieval returns the existing credential while it is valid. After it expires, retrieving it creates a new random credential and invalidates the previous QR. The seller panel refreshes the displayed QR automatically when its expiration time is reached. The seller can also immediately regenerate the QR, which revokes the old QR and any sessions created with it.

## 3. Logout and expiration handling

`POST /api/delivery-boy/logout` requires `Authorization: Bearer <access_token>`:

```http
POST /api/delivery-boy/logout
Authorization: Bearer 42|sanctum-personal-access-token
Accept: application/json
```

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider logged out.",
  "data": []
}
```

Also clear local credentials on any `401` or `403` from protected rider endpoints. The backend rejects password tokens when the approval deadline passes and rejects QR tokens when the QR session expires. Password-login approval is separate from QR login; seller rejection revokes password-login tokens.
