# Seller App Login and Approval API

Base URL examples below use `https://api.example.com`. All responses are JSON. Send `Accept: application/json`; send requests as `Content-Type: application/json` unless noted otherwise.

## 1. Seller app login

`POST /api/seller/login` is public. A seller owner can sign in normally. A seller-created system user must have an active approval grant before login returns an access token.

Request by email:

```http
POST /api/seller/login
Accept: application/json
Content-Type: application/json
```

```json
{
    "email": "staff@example.com",
    "password": "your-password",
    "fcm_token": "optional-device-fcm-token",
    "device_type": "android"
}
```

Use either `email` or `mobile`. `password` is required. `fcm_token` and `device_type` are optional and used for push notifications.

### Login approved

HTTP `200`:

```json
{
    "success": true,
    "message": "Login successful.",
    "access_token": "1|sanctum-personal-access-token",
    "token_type": "Bearer",
    "data": {
        "id": 196,
        "name": "Staff User",
        "email": "staff@example.com",
        "mobile": "9876543210",
        "country": "India",
        "iso_2": "IN",
        "wallet_balance": 0,
        "blocked_balance": 0,
        "available_balance": "0.00",
        "referral_code": null,
        "friends_code": null,
        "reward_points": 0,
        "profile_image": null,
        "email_verified_at": null,
        "created_at": "2026-10-07 12:00:00",
        "updated_at": "2026-10-07 12:00:00"
    },
    "assigned_permissions": ["product.view", "order.view"]
}
```

`data` is the user resource and may contain additional profile fields. Store `access_token` securely. Send it on protected requests:

```http
Authorization: Bearer 1|sanctum-personal-access-token
Accept: application/json
```

Continue to the seller dashboard after successful login.

### Approval pending, disapproved, or expired

HTTP `403`; **no access token is issued**:

```json
{
    "success": false,
    "message": "Your login request is waiting for seller approval.",
    "data": {
        "login_approval_status": "pending",
        "approval_requested_at": "2026-10-07T12:00:00.000000Z"
    }
}
```

For the app flow, display a pending-approval screen. When the user taps **Check approval**, retry the same login request with their credentials. Do not persist the password just to poll. While approval is pending, another login attempt returns the same pending response; after the seller approves, the retry returns the normal `200` login response and token. The API currently has no separate approval-status polling endpoint for unauthenticated system users.

Invalid credentials return `success: false` with `message` and an empty `data` object. Validation errors can return the same envelope with a validation message. Do not treat an unsuccessful response as approval pending unless `data.login_approval_status` is `pending`.

## 2. Seller admin: list pending approvals

Requires the seller administrator's valid Sanctum token and the seller system-user view permission.

```http
GET /api/seller/system-users/login-approvals?per_page=15
Authorization: Bearer <seller-admin-token>
Accept: application/json
```

HTTP `200`:

```json
{
    "success": true,
    "message": "Pending login approvals retrieved.",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "user_id": 196,
                "name": "Staff User",
                "email": "staff@example.com",
                "mobile": "9876543210",
                "login_approval_status": "pending",
                "login_approval_requested_at": "2026-10-07T12:00:00.000000Z",
                "login_approved_until": null
            }
        ]
    }
}
```

The endpoint returns only pending requests belonging to the authenticated seller. `per_page` is optional.

## 3. Approve or disapprove a user

Both endpoints require the seller admin's Sanctum token and permission to update that system user. No request body is required.

Approve for 24 hours:

```http
POST /api/seller/system-users/196/approve-login
Authorization: Bearer <seller-admin-token>
Accept: application/json
```

HTTP `200`:

```json
{
    "success": true,
    "message": "System user login approval updated.",
    "data": {
        "user_id": 196,
        "login_approval_status": "approved",
        "login_approved_until": "2026-10-08T12:00:00.000000Z"
    }
}
```

Disapprove immediately:

```http
POST /api/seller/system-users/196/reject-login
Authorization: Bearer <seller-admin-token>
Accept: application/json
```

HTTP `200`:

```json
{
    "success": true,
    "message": "System user login approval updated.",
    "data": {
        "user_id": 196,
        "login_approval_status": "disapproved",
        "login_approved_until": null
    }
}
```

Despite the route's legacy `reject-login` name, this action sets the status to `disapproved`. Disapproval deletes the user's Sanctum tokens. A later protected API request with that revoked token normally returns HTTP `401` from Sanctum. A still-valid token whose approval window has just expired is rejected by the approval middleware with HTTP `403`:

```json
{
    "success": false,
    "message": "Seller approval is required to access this account.",
    "data": {
        "login_approval_status": "disapproved"
    }
}
```

For either `401` or `403`, clear the stored token and return the user to the login/pending flow. Do not continue using a cached token after an authorization failure.

## 4. Approval notifications

When a system user requests access, the seller administrator receives a notification and, when an FCM token is registered, a push notification with data similar to:

```json
{
    "type": "seller_system_user_login_approval",
    "system_user_id": "196",
    "seller_id": "42"
}
```

Use the IDs to refresh the pending approvals list or open the target system-user row. Notifications are a prompt only; the approve/disapprove API responses are the source of truth for status.

## 5. Status and session rules

- Approval applies to seller-created system users; the main seller account owner does not require system-user approval.
- Each manual approval grants access until `login_approved_until`, currently 24 hours after approval.
- The app should treat that timestamp as authoritative and may refresh login when it expires.
- Approval expiry is enforced on authenticated seller API requests. On HTTP `401` (revoked token) or `403` (expired approval), clear the saved access token and return the user to the login/pending flow.
- A successful login is the only point at which the system user receives a new API access token after approval.
