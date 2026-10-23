# Seller Delivery Boy Login Controls API

These authenticated seller endpoints manage seller-owned rider accounts, password-login approvals, and QR credentials. Base URL examples use `https://api.example.com`. Send `Accept: application/json`; JSON requests use `Content-Type: application/json`.

Every endpoint requires the seller owner's or authorized seller system user's Sanctum token:

```http
Authorization: Bearer <seller-access-token>
Accept: application/json
```

System-user roles need `delivery_boy.view` to list riders and `delivery_boy.manage` to claim/create riders, approve/disapprove password login, and retrieve QR credentials. Assigned riders are scoped to the authenticated seller. Unassigned legacy platform riders are exposed in a separate available list so a seller can explicitly claim one; an ID already assigned to another seller is not accessible.

## 1. List seller riders and approval state

`GET /api/seller/delivery-boys?search=&per_page=15&available_page=1`

Optional query parameters:

- `search`: matches rider name, email, or mobile.
- `per_page`: 1–100; default 15.
- `available_page`: page number for unassigned platform riders available to claim (default 1).

`available_riders` contains unassigned legacy riders only for users who have the `delivery_boy.manage` permission; other users receive an empty available-rider page. The seller web panel has the same **Add to my team** action.

HTTP `200`:

```json
{
  "success": true,
  "message": "Riders retrieved.",
  "data": {
    "assigned_riders": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1,
      "data": [
      {
        "id": 17,
        "user_id": 42,
        "name": "Rider Name",
        "email": "rider@example.com",
        "mobile": "9876543210",
        "delivery_zone_id": 3,
        "status": "active",
        "verification_status": "verified",
        "login_approval_status": "pending",
        "login_approval_requested_at": "2026-10-23T20:00:00.000000Z",
        "login_approved_until": null,
        "qr_login_expires_at": "2026-10-24T08:00:00.000000Z"
      }
      ]
    },
    "available_riders": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1,
      "data": [
        {
          "id": 21,
          "user_id": 64,
          "name": "Existing Rider",
          "email": "existing-rider@example.com",
          "mobile": "9876500000",
          "delivery_zone_id": 3,
          "status": "active",
          "verification_status": "verified",
          "login_approval_status": "disapproved",
          "login_approval_requested_at": null,
          "login_approved_until": null,
          "qr_login_expires_at": null
        }
      ]
    }
  }
}
```

`login_approval_status` is `disapproved`, `pending`, or `approved`. A grant is active only while `login_approved_until` is in the future. `qr_login_expires_at` can be null until a QR is first requested.

### Claim an existing platform rider

`POST /api/seller/delivery-boys/{rider_id}/claim`

No request body is required. The operation only succeeds if the rider is still unassigned. It assigns the rider to the authenticated seller, resets password approval and QR credentials, and revokes existing access tokens. Rider profile and platform verification details are preserved.

HTTP `200`:

```json
{
  "success": true,
  "message": "Existing delivery boy added to your seller account.",
  "data": {
    "rider": {
      "id": 21,
      "user_id": 64,
      "name": "Existing Rider",
      "email": "existing-rider@example.com",
      "mobile": "9876500000",
      "delivery_zone_id": 3,
      "status": "active",
      "verification_status": "verified",
      "login_approval_status": "disapproved",
      "login_approval_requested_at": null,
      "login_approved_until": null,
      "qr_login_expires_at": null
    }
  }
}
```

If another seller claims the rider first, the response is HTTP `409` with `data.code = "rider_already_assigned"`. After claiming, the rider appears in `assigned_riders` and seller login controls apply.

## 2. Create a rider account

`POST /api/seller/delivery-boys`

Request:

```json
{
  "full_name": "Rider Name",
  "email": "rider@example.com",
  "mobile": "9876543210",
  "password": "temporary-password",
  "delivery_zone_id": 3
}
```

`full_name`, unique `email`, `password` (minimum 8 characters), and an active `delivery_zone_id` are required. `mobile` is optional. Use the existing `GET /api/delivery-zone` endpoint to populate the zone selector.

HTTP `201`:

```json
{
  "success": true,
  "message": "Rider created. Platform verification is required before login.",
  "data": {
    "rider": {
      "id": 17,
      "user_id": 42,
      "name": "Rider Name",
      "email": "rider@example.com",
      "mobile": "9876543210",
      "delivery_zone_id": 3,
      "status": "inactive",
      "verification_status": "pending",
      "login_approval_status": "disapproved",
      "login_approval_requested_at": null,
      "login_approved_until": null,
      "qr_login_expires_at": null
    }
  }
}
```

The platform admin must verify and activate the rider before rider login is available. Existing platform riders can be assigned to a seller from the platform admin's rider detail page or claimed from `available_riders`; either ownership change revokes existing tokens and resets approval/QR state.

## 3. Approve password login

`POST /api/seller/delivery-boys/{rider_id}/approve-login`

No request body is required. The platform-configured duration is applied (default 24 hours).

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider password login approved.",
  "data": {
    "rider_id": 17,
    "login_approval_status": "approved",
    "login_approved_until": "2026-10-24T08:00:00.000000Z"
  }
}
```

After approval, the rider retries the email/password login call and receives an access token expiring at `login_approved_until`.

## 4. Disapprove password login

`POST /api/seller/delivery-boys/{rider_id}/reject-login`

No request body is required. This sets `login_approval_status` to `disapproved`, clears `login_approved_until`, and revokes existing password-login tokens.

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider password login disapproved.",
  "data": {
    "rider_id": 17,
    "login_approval_status": "disapproved",
    "login_approved_until": null
  }
}
```

This approval decision controls password login. QR login is a separate credential and remains valid until its own expiration or until the seller rotates it.

## 5. Retrieve or refresh the rider QR

`GET /api/seller/delivery-boys/{rider_id}/login-qr`

No request body is required. The credential can only be retrieved for a platform-verified seller rider.

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider QR login credential retrieved.",
  "data": {
    "rider_id": 17,
    "qr_token": "opaque-64-character-random-rider-credential",
    "qr_payload": "opaque-64-character-random-rider-credential",
    "expires_at": "2026-10-24T08:00:00.000000Z"
  }
}
```

Render `qr_payload` as a QR code in the seller app. The rider app scans the QR and sends its exact contents as `qr_token` to `POST /api/delivery-boy/login/qr`. The API returns HTTP `422` if the rider is not verified. Credentials are encrypted at rest and indexed by a one-way hash; they must not be logged or shared.

An unexpired request returns the same current QR credential. Once expired, the next request atomically creates a new random credential; the old QR stops working. The seller web panel at `/seller/delivery-boys` displays the QR and refreshes it automatically at expiry.

To immediately invalidate an exposed QR:

`POST /api/seller/delivery-boys/{rider_id}/rotate-login-qr`

No request body is required. The endpoint returns the same response structure as the GET endpoint with a new `qr_token`, `qr_payload`, and `expires_at`. It invalidates the previous QR and any sessions authenticated with that QR. The seller panel's **Regenerate QR** button uses this endpoint.

## 6. Errors

Validation errors return HTTP `422`. Missing seller context returns HTTP `404`. Insufficient seller permission returns HTTP `403`. A rider ID outside the authenticated seller's scope returns HTTP `404`.

## 7. Platform duration setting

The main platform admin configures `riderLoginDurationHours` in **Admin → Settings → Delivery Boy** (integer, 1–720; default 24). The value controls both the duration of a password approval grant and newly generated QR credentials. It is not seller-editable. The seller web panel uses the same API and displays the configured duration.
