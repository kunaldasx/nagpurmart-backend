# User Address API

All endpoints below require a Sanctum bearer token.

Base path: `/api/user/addresses`

## Address types

`address_type` is required when creating an address and accepts exactly:

- `home`
- `office`
- `other`

The type is a label for the saved address. Users may save any number of addresses, including multiple addresses with the same type.

## Create an address

```http
POST /api/user/addresses
Authorization: Bearer <token>
Content-Type: application/json
```

Request body:

```json
{
    "address_line1": "Building / Floor",
    "address_line2": "43XJ+RJ5, Sitabuldi",
    "city": "Nagpur",
    "landmark": "Near Central Mall",
    "state": "Maharashtra",
    "zipcode": "440012",
    "mobile": "9394070912",
    "address_type": "home",
    "country": "India",
    "country_code": "IN",
    "latitude": 21.1458,
    "longitude": 79.0882
}
```

Required fields: `address_line1`, `city`, `state`, `zipcode`, `mobile`, `address_type`, `country`, `country_code`, `latitude`, and `longitude`.

`address_line2` and `landmark` are optional. Coordinates must be supplied so the API can confirm that the address is inside a delivery zone.

Successful response (`201`):

```json
{
    "success": true,
    "message": "Address created successfully",
    "data": {
        "id": 42,
        "user_id": 7,
        "address_line1": "Building / Floor",
        "address_line2": "43XJ+RJ5, Sitabuldi",
        "city": "Nagpur",
        "landmark": "Near Central Mall",
        "state": "Maharashtra",
        "zipcode": "440012",
        "mobile": "9394070912",
        "address_type": "home",
        "country": "India",
        "country_code": "IN",
        "latitude": 21.1458,
        "longitude": 79.0882,
        "created_at": "2026-09-20 12:00:00",
        "updated_at": "2026-09-20 12:00:00"
    }
}
```

## List saved addresses

```http
GET /api/user/addresses?address_type=office&page=1&per_page=15
Authorization: Bearer <token>
```

Supported query parameters include `address_type`, `query`, `city`, `state`, `country`, `zone_id`, `page`, `per_page`, `sort`, and `order`. Omit `address_type` to return home, office, and other addresses together.

Response (`200`):

```json
{
    "success": true,
    "message": "Addresses retrieved successfully",
    "data": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1,
        "data": [
            {
                "id": 42,
                "user_id": 7,
                "address_line1": "Building / Floor",
                "address_line2": "43XJ+RJ5, Sitabuldi",
                "city": "Nagpur",
                "landmark": "Near Central Mall",
                "state": "Maharashtra",
                "zipcode": "440012",
                "mobile": "9394070912",
                "address_type": "office",
                "country": "India",
                "country_code": "IN",
                "latitude": 21.1458,
                "longitude": 79.0882,
                "created_at": "2026-09-20 12:00:00",
                "updated_at": "2026-09-20 12:00:00"
            }
        ]
    }
}
```

## Get one address

```http
GET /api/user/addresses/{id}
Authorization: Bearer <token>
```

Only the authenticated user's address can be returned. A missing or foreign address returns `404`.

## Update an address

Use `PATCH` for a partial update or `PUT` with the fields being changed:

```http
PATCH /api/user/addresses/42
Authorization: Bearer <token>
Content-Type: application/json
```

Example request:

```json
{
    "address_type": "office",
    "landmark": "Opposite Metro Station"
}
```

`address_type` is required in update requests and must be `home`, `office`, or `other`. If latitude or longitude is changed, the updated point is checked against delivery zones.

The response is the same single-address structure as the create response.

## Delete an address

```http
DELETE /api/user/addresses/{id}
Authorization: Bearer <token>
```

Response (`200`):

```json
{
    "success": true,
    "message": "Address deleted successfully",
    "data": null
}
```

## Validation error

Invalid address types, missing required fields, invalid coordinates, or an address outside delivery zones return `422`:

```json
{
    "success": false,
    "message": "Validation failed",
    "data": {
        "address_type": ["The selected address type is invalid."]
    }
}
```

## Use an address during checkout

Create the order with the selected saved address ID:

```json
{
    "address_id": 42,
    "payment_type": "cod"
}
```

The checkout service verifies that the selected address belongs to the authenticated user before copying it into the order's shipping address.
