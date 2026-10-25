# Seller App: System User Status and Login Approval

This guide describes the seller app UI and API integration for displaying the
active/inactive state of seller system users and managing their login approval.
The API base URL is environment-specific. Examples below use
`https://api.example.com`; routes shown with `/api` are relative to that host.

All requests and responses are JSON. Send `Accept: application/json`; send
`Content-Type: application/json` when a request has a body.

## 1. Status concepts

The API exposes two different statuses. Show them as separate fields in the
seller app:

| Field | Values | Meaning |
| --- | --- | --- |
| `system_user_status` | `active`, `inactive` | Whether the system user has an active seller-app session. |
| `login_approval_status` | `not_requested`, `pending`, `approved`, `disapproved` | Whether the seller has granted the user login access. |

Do not derive `system_user_status` from approval status. A user may be approved
but inactive because they have not logged in (or have logged out). A user who
is logged in is active until they log out, are disapproved, or their approval
expires.

Suggested UI:

- Show **Active** with a green badge when `system_user_status` is `active`.
- Show **Inactive** with a neutral badge when it is `inactive`.
- Show approval status separately. For an approved grant, show
  `login_approved_until` in the seller's local time if present.
- Refresh the list after approval actions and when returning to the screen.
  The status is persisted server-side; do not infer it from a local app session.

## 2. Authentication headers

Seller-admin endpoints require the seller admin's Sanctum token:

```http
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
Content-Type: application/json
```

The system-user login endpoint is public. On successful login, securely store
the returned `access_token` and send it as a Bearer token to protected endpoints.
Never log or expose token values.

## 3. List seller system users

```http
GET /api/seller/system-users?per_page=15&search=packer
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
```

Both query parameters are optional. `per_page` defaults to `15`; `search`
matches name, email, or mobile. The endpoint returns only users associated with
the authenticated seller.

HTTP `200`:

```json
{
  "success": true,
  "message": "Users fetched successfully.",
  "data": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "data": [
      {
        "id": 196,
        "name": "Staff User",
        "email": "staff@example.com",
        "mobile": "9876543210",
        "login_approval_status": "approved",
        "login_approval_requested_at": "2026-10-25T08:00:00.000000Z",
        "login_approved_until": "2026-10-26T08:00:00.000000Z",
        "system_user_status": "active"
      }
    ]
  }
}
```

The actual user row also contains the user model's other public fields (for
example, `access_panel`, timestamps, and `profile_image`). Treat the two status
fields and approval timestamps as the authoritative fields for this feature.
`login_approval_requested_at` and `login_approved_until` may be `null`.

## 4. Get a system user's details

```http
GET /api/seller/system-users/{userId}
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
```

Example: `GET /api/seller/system-users/196`

HTTP `200`:

```json
{
  "success": true,
  "message": "User retrieved.",
  "data": {
    "user": {
      "id": 196,
      "name": "Staff User",
      "email": "staff@example.com",
      "mobile": "9876543210"
    },
    "login_approval_status": "approved",
    "login_approval_requested_at": "2026-10-25T08:00:00.000000Z",
    "login_approved_until": "2026-10-26T08:00:00.000000Z",
    "system_user_status": "active"
  }
}
```

The `user` object contains the user's public model fields and roles; its
remaining fields can vary as the model evolves.

## 5. List pending login approvals

This endpoint is for a seller-admin approval inbox. It returns only pending
requests for the authenticated seller.

```http
GET /api/seller/system-users/login-approvals?per_page=15
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
```

`per_page` is optional and defaults to `15`.

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
        "login_approval_requested_at": "2026-10-25T08:00:00.000000Z",
        "login_approved_until": null,
        "system_user_status": "inactive"
      }
    ]
  }
}
```

Use `user_id` as the `{userId}` for the approval actions below.

## 6. Approve a system user's login

```http
POST /api/seller/system-users/{userId}/approve-login
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
Content-Type: application/json
```

An optional custom grant duration may be sent in hours:

```json
{
  "duration_hours": 6
}
```

If omitted, the grant defaults to up to 12 hours, capped by the platform's
configured maximum. The server enforces the configured bounds.

HTTP `200`:

```json
{
  "success": true,
  "message": "System user login approval updated.",
  "data": {
    "user_id": 196,
    "login_approval_status": "approved",
    "login_approved_until": "2026-10-25T14:00:00.000000Z",
    "system_user_status": "inactive",
    "duration_hours": 6
  }
}
```

Approval grants permission to log in; it does not itself start a session. Thus
the user's status commonly remains `inactive` until they successfully log in.
Refresh the user list or update the approval fields from this response.

## 7. Disapprove a system user's login

The route retains the legacy name `reject-login`; it disapproves the grant and
revokes the user's existing Sanctum tokens.

```http
POST /api/seller/system-users/{userId}/reject-login
Authorization: Bearer <seller_admin_access_token>
Accept: application/json
```

No request body is required.

HTTP `200`:

```json
{
  "success": true,
  "message": "System user login approval updated.",
  "data": {
    "user_id": 196,
    "login_approval_status": "disapproved",
    "login_approved_until": null,
    "system_user_status": "inactive",
    "duration_hours": null
  }
}
```

Update the local row immediately from this response, then refresh the list if
needed.

## 8. System-user login and pending approval

When a system user signs in to the seller app, use:

```http
POST /api/seller/login
Accept: application/json
Content-Type: application/json
```

Email request:

```json
{
  "email": "staff@example.com",
  "password": "<user password>",
  "fcm_token": "<optional device token>",
  "device_type": "android"
}
```

Alternatively use `mobile` instead of `email`. Password is required;
`fcm_token` and `device_type` are optional.

### Approved login

HTTP `200`:

```json
{
  "success": true,
  "message": "Login successful.",
  "access_token": "<sanctum access token>",
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
    "created_at": "2026-10-25 08:00:00",
    "updated_at": "2026-10-25 08:00:00"
  },
  "assigned_permissions": [
    "product.view",
    "order.view"
  ]
}
```

Save the token securely and continue into the app. Successful system-user login
sets `system_user_status` to `active`.

### Approval pending or expired

When a system user without a currently valid grant tries to log in, no API token
is issued. The login request creates a pending approval request and returns
HTTP `403`:

```json
{
  "success": false,
  "message": "Your login request is waiting for seller approval.",
  "data": {
    "login_approval_status": "pending",
    "approval_requested_at": "2026-10-25T08:00:00.000000Z"
  }
}
```

Show a pending approval screen. To check again, have the user retry login with
their credentials; do not persist their password for polling. Once approved,
the retry returns the normal successful-login response and token. Do not treat
all failed login responses as pending: check
`data.login_approval_status === "pending"`.

Invalid credentials currently return `success: false`, an invalid-credentials
message, and `data: []` (the HTTP status may still be `200`). Validation errors
also use a `success: false` envelope. Always check `success` and the response
data, not only the HTTP status.

## 9. Logout

Call logout before clearing the locally stored token:

```http
POST /api/seller/logout
Authorization: Bearer <system_user_access_token>
Accept: application/json
```

No request body is required.

HTTP `200`:

```json
{
  "success": true,
  "message": "Logout successful.",
  "data": []
}
```

The server revokes the current token and marks the system user inactive. Clear
the local token and return to the login screen after successful logout.

## 10. Error handling and session expiration

Seller-admin endpoints use the standard response envelope:

```json
{
  "success": false,
  "message": "Permission denied.",
  "data": null
}
```

Common outcomes:

| HTTP status | Meaning | Suggested client behavior |
| --- | --- | --- |
| `401` | Missing, invalid, or revoked token | Clear the saved token and return to login. |
| `403` | Permission denied, or seller approval required | For the pending-login payload, show pending approval. Otherwise show the message and respect the authorization failure. |
| `404` | User or seller not found | Refresh the relevant screen; show a not-found message if it persists. |
| `422` | Request validation failed | Show field-level validation feedback where available. |

If an approval expires, the user becomes inactive and their sessions are
revoked by the scheduled expiry processing. Expiry is also enforced when
protected seller endpoints are accessed. On `401`/`403` from a protected
request, clear local credentials and route the user back through login and the
approval-pending flow.

## 11. Suggested seller app flow

1. Fetch `GET /api/seller/system-users` to show the user list, both status
   badges, and the approval expiry when present.
2. Optionally provide a pending-approval inbox using
   `GET /api/seller/system-users/login-approvals`.
3. Approve or disapprove a pending user using the corresponding action. Apply
   the returned fields to the row and refresh the list/inbox.
4. Use `system_user_status` as the displayed activity state. Do not equate
   approval with being logged in.
5. When a system user logs out, call the logout endpoint and clear the token.
   When a request is unauthorized, clear the token and return to login.
6. Refresh system-user data when the screen is opened or resumed so that
   activity and approval expiry changes made elsewhere are reflected.

Approval notifications are only prompts to refresh the pending-approval list;
the API response is the source of truth. Notification data may include
`type: "seller_system_user_login_approval"`, `system_user_id`, and `seller_id`.
