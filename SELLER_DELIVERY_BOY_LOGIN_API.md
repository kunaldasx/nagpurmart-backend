# Seller Delivery Boy Login Controls API

These authenticated seller endpoints manage seller-owned rider accounts, password-login approvals, and QR credentials. Base URL examples use `https://api.example.com`. Send `Accept: application/json`; JSON requests use `Content-Type: application/json`.

Every endpoint requires the seller owner's or authorized seller system user's Sanctum token:

```http
Authorization: Bearer <seller-access-token>
Accept: application/json
```

System-user roles need `delivery_boy.view` to list riders and `delivery_boy.manage` to claim/create riders, approve/disapprove password login, and retrieve QR credentials. Assigned riders are scoped to the authenticated seller. Unassigned legacy platform riders are exposed in a separate available list so a seller can explicitly claim one; an ID already assigned to another seller is not accessible.

## Seller app rider list and status UI

Use `GET /api/seller/delivery-boys` to build the seller app's delivery-boys page. Render each item in `data.assigned_riders.data` as a rider row or card with the rider name, email, status, verification status, and login controls. Add a **Status** column or badge sourced from `status`:

- `active`: show a green **ACTIVE** badge.
- `inactive`: show a neutral/gray **INACTIVE** badge.

Treat `status` as the source of truth; do not infer it from `login_approval_status` or `verification_status`. A rider can be verified and approved but remain inactive until they actually log in. After an approval/disapproval action or QR rotation, refresh the list. Refresh it when the seller opens or returns to the page; the backend reconciles expired sessions at least once per minute.

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
        "status": "inactive",
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
          "status": "inactive",
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

`status` is `active` only while the rider has logged in and has not logged out, been disapproved, or had the current login session expire. Otherwise it is `inactive`. Successful QR or approved email/password login sets status to `active`. Logout, seller disapproval, and expiration of the current QR or password login token set it to `inactive`. Expired sessions are reconciled at least once per minute and when an expired token is used.

`login_approval_status` is `disapproved`, `pending`, or `approved`. A password-login grant is valid only while `login_approved_until` is in the future. `qr_login_expires_at` can be null until a QR is first requested. Approval grants permission to use email/password; approval alone does not log the rider in.

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
      "status": "inactive",
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

Optional JSON body:

```json
{
  "duration_hours": 6
}
```

`duration_hours` must be an integer from 1 through the platform-configured `riderLoginDurationHours` maximum (1–720 hours). If omitted, the default is 12 hours, or the platform maximum when it is below 12.

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider password login approved.",
  "data": {
    "rider_id": 17,
    "login_approval_status": "approved",
    "login_approved_until": "2026-10-24T08:00:00.000000Z",
    "status": "inactive",
    "duration_hours": 6
  }
}
```

Approval does not change `status` to active. After approval, the rider retries the email/password login call and receives an access token expiring at `login_approved_until`; successful login changes `status` to `active`.

## 4. Disapprove password login

`POST /api/seller/delivery-boys/{rider_id}/reject-login`

No request body is required. This sets `login_approval_status` to `disapproved`, clears `login_approved_until`, marks the rider `inactive`, and revokes all current rider login sessions.

HTTP `200`:

```json
{
  "success": true,
  "message": "Rider password login disapproved.",
  "data": {
    "rider_id": 17,
    "login_approval_status": "disapproved",
    "login_approved_until": null,
    "status": "inactive"
  }
}
```

Disapproval revokes the current session, regardless of which login method created it. The QR credential itself remains separate and can start a new session until it expires or the seller rotates it.

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

When a rider is logged in with a QR session, the session ends at `expires_at` and the rider status becomes `inactive`. Refresh the seller rider list after expiry so the status badge updates.

To immediately invalidate an exposed QR:

`POST /api/seller/delivery-boys/{rider_id}/rotate-login-qr`

Request body:

```json
{
  "duration_hours": 6
}
```

`duration_hours` is required and must be an integer from 1 through the platform-configured `riderLoginDurationHours` limit (1–720 hours). The endpoint returns the same response structure as the GET endpoint with a new `qr_token`, `qr_payload`, and `expires_at`. It invalidates the previous QR and any sessions authenticated with that QR; when that was the rider's active session, the status becomes `inactive`. The seller panel's **Regenerate QR** button uses this endpoint and allows the seller to choose any validity up to the platform limit.

Seller rider management also supports:

- `POST /api/seller/delivery-boys/{rider_id}` to update `full_name`, `email`, `mobile`, `delivery_zone_id`, and optionally `password`. The email must remain unique; an omitted or empty password is left unchanged. The zone must be active. Changing the password revokes all existing rider access tokens and marks the rider inactive.

Request:

```json
{
  "full_name": "Updated Rider Name",
  "email": "updated-rider@example.com",
  "mobile": "9876543210",
  "delivery_zone_id": 3,
  "password": "optional-new-password"
}
```

`full_name`, `email`, and `delivery_zone_id` are required. `mobile` and `password` are optional; an omitted mobile is unchanged, and an omitted or empty password is unchanged.

HTTP `200`:

```json
{
  "success": true,
  "message": "Delivery boy updated.",
  "data": {
    "rider": {
      "id": 17,
      "user_id": 42,
      "name": "Updated Rider Name",
      "email": "updated-rider@example.com",
      "mobile": "9876543210",
      "delivery_zone_id": 3,
      "status": "inactive",
      "verification_status": "verified",
      "login_approval_status": "approved",
      "login_approval_requested_at": null,
      "login_approved_until": "2026-10-24T08:00:00.000000Z",
      "qr_login_expires_at": "2026-10-24T08:00:00.000000Z"
    }
  }
}
```

- `DELETE /api/seller/delivery-boys/{rider_id}` soft-deletes the seller-owned rider and user accounts and revokes all their active access tokens.

HTTP `200`:

```json
{
  "success": true,
  "message": "Delivery boy deleted.",
  "data": []
}
```

Both operations require `delivery_boy.manage` and are scoped to the authenticated seller. Update returns HTTP `200` with `data.rider` in the same shape as a listed rider; delete returns HTTP `200` with an empty `data` value. Validation errors return HTTP `422`.

## 6. Errors

Validation errors return HTTP `422`. Missing seller context returns HTTP `404`. Insufficient seller permission returns HTTP `403`. A rider ID outside the authenticated seller's scope returns HTTP `404`.

## 7. Platform duration setting

The main platform admin configures `riderLoginDurationHours` in **Admin → Settings → Delivery Boy** (integer, 1–720; default 24). The value is the maximum seller-selected duration for password approvals and QR credentials. Sellers choose each password approval duration; its default is 12 hours, capped at the platform maximum when that is lower. The platform maximum is not seller-editable.
