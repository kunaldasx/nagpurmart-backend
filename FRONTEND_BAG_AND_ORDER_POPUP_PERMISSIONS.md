# Frontend instructions: Bag permissions and seller order popup permissions

This document is for the frontend app that consumes the seller permission APIs.

## 1) Backend permission names

These seller permissions were added for the role/permission system:

### Bag permissions

- `bag.view`
- `bag.create`
- `bag.edit`
- `bag.delete`

### Order permissions

- `order.view`
- `order.edit`
- `order.update_status`
- `order.popup`

The default seller role is still allowed to access the popup automatically.

---

## 2) API to fetch grouped permissions for a seller role

Endpoint:
GET /api/seller/permissions/{roleName}

Example:
GET /api/seller/permissions/Inventory Manager

### Request

No request body.

### Response shape

```json
{
    "success": true,
    "message": "Permissions fetched successfully.",
    "data": {
        "role": {
            "id": 12,
            "name": "Inventory Manager"
        },
        "grouped_permissions": {
            "dashboard": {
                "name": "Dashboard",
                "permissions": ["dashboard.view"]
            },
            "order": {
                "name": "Order",
                "permissions": [
                    "order.view",
                    "order.edit",
                    "order.update_status",
                    "order.popup"
                ]
            },
            "bag": {
                "name": "Bag",
                "permissions": [
                    "bag.view",
                    "bag.create",
                    "bag.edit",
                    "bag.delete"
                ]
            }
        },
        "assigned": ["dashboard.view", "order.view", "order.popup", "bag.view"]
    }
}
```

### What frontend should do

Use `response.data.assigned` as the list of permissions for the current role.

---

## 3) API to update permissions for a role

Endpoint:
POST /api/seller/permissions

### Request body

```json
{
    "role": "Inventory Manager",
    "permissions": [
        "dashboard.view",
        "order.view",
        "order.popup",
        "bag.view",
        "bag.create",
        "bag.edit",
        "bag.delete"
    ]
}
```

### Response shape

```json
{
    "success": true,
    "message": "Permissions updated successfully.",
    "data": [
        {
            "id": 1,
            "name": "dashboard.view",
            "guard_name": "seller"
        },
        {
            "id": 2,
            "name": "order.view",
            "guard_name": "seller"
        }
    ]
}
```

---

## 4) Popup permission behavior

The incoming seller order popup now requires the `order.popup` permission.

### Frontend condition

```ts
const isDefaultSeller = user.role === "seller";
const canShowOrderPopup =
    isDefaultSeller || assignedPermissions.includes("order.popup");
```

If `canShowOrderPopup` is false, do not render the seller popup modal and do not poll the pending orders endpoint for the popup alert.

### Backend fallback rule

The backend also returns empty popup data when the user does not have `order.popup` and is not the default seller role.

Example response:

```json
{
    "success": true,
    "message": "Pending orders fetched successfully",
    "data": {
        "orders": [],
        "count": 0,
        "order_mode": "regular"
    }
}
```

---

## 5) Frontend implementation checklist

### Bag UI

- If `bag.view` is missing, do not render the Bag page.
- If `bag.create` is missing, hide the Add Bag button.
- If `bag.edit` is missing, disable edit actions.
- If `bag.delete` is missing, disable delete actions.

### Seller order popup UI

- If `order.popup` is missing, hide the incoming-order alert modal.
- If the role is default seller, keep it visible by default.

### Example permission helper

```ts
const hasPermission = (permission: string, assigned: string[]) =>
    assigned.includes(permission);

const permissions = response.data.assigned;

const canViewBag = hasPermission("bag.view", permissions);
const canCreateBag = hasPermission("bag.create", permissions);
const canEditBag = hasPermission("bag.edit", permissions);
const canDeleteBag = hasPermission("bag.delete", permissions);
const canShowPopup = hasPermission("order.popup", permissions);
```

---

## 6) Recommended frontend flow

1. Load the current user session.
2. Fetch the permissions for the selected role.
3. Store them in a global auth/permission state.
4. Use the permission checks to decide whether to show pages, buttons, and popup flows.
5. When updating permissions from the roles screen, send the full selected list to the backend.

---

## 7) Important notes

- The permission names are exact strings; do not change casing or spacing.
- Backend permission names are all lowercase with dots, e.g. `bag.view` and `order.popup`.
- Do not hide the popup based only on page route checks; always check the actual permission list.
- The default seller role should still behave as an allowed fallback unless your product requirement says otherwise.

---

## 8) Exact permissions summary

```json
{
    "bag": ["bag.view", "bag.create", "bag.edit", "bag.delete"],
    "order": ["order.view", "order.edit", "order.update_status", "order.popup"]
}
```

This is the contract the frontend app should follow.
