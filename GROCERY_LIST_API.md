# Grocery list image API

Base URL: `/api`

History endpoints require the customer’s Sanctum bearer token:
`Authorization: Bearer <token>`

## Extract a list

`POST /user/grocery-lists`

Send `multipart/form-data` with one field:

| Field   | Type | Required | Details                                |
| ------- | ---- | -------- | -------------------------------------- |
| `image` | file | Yes      | JPG, JPEG, PNG, or WEBP; maximum 10 MB |

The endpoint is temporarily public while frontend authentication is being integrated. If a bearer token is supplied, the scan is linked to that user; without one, the extracted output is stored as an anonymous record. The uploaded image is processed in memory and is not stored. It validates the file, sends it to Gemini for extraction, and returns only the structured grocery items. Product searching is not performed.

Optional form field: `model_id`. It must be one of `gemini-3.5-flash-lite`, `gemini-3.5-flash`, `gemini-3.1-pro-preview`, or `gemini-2.5-flash`. If omitted, the admin-selected model is used. If invalid, the request uses `gemini-3.5-flash` and returns a non-blocking warning in `metadata.warning`.

Successful response data contains `items` and `metadata`. Each item contains exactly `english name`.

Example response:

```json
{
    "success": true,
    "message": "Grocery list extracted successfully.",
    "data": {
        "items": [{ "english name": "Onion" }, { "english name": "Milk" }],
        "metadata": { "list_id": 12, "model": "gemini-3.5-flash" }
    }
}
```

Non-grocery images return HTTP `422` with `status: rejected`, an empty item list, and `rejection_reason`. Validation errors also return `422`; an unavailable AI provider returns `500`.

## List previous uploads

`GET /user/grocery-lists?per_page=15`

Returns paginated scans belonging only to the authenticated user, including their extracted items. Images are not retained.

## Get one upload

`GET /user/grocery-lists/{id}`

Returns one list owned by the authenticated user, or `404` when it does not exist.

## Configuration

Configure the key and model in Admin Panel > Settings > System. The admin model setting takes precedence; when it is blank, the service uses `GEMINI_MODEL` from the server `.env`, defaulting to `gemini-3.5-flash`. If a selected model returns 404 or remains truncated, the service falls back to `gemini-3.5-flash`. The prompt reads up to 50 visible lines, emits one item for each distinct grocery even when several appear on one line, translates or transliterates Hindi/Marathi and handwriting, uses JSON mode, temperature 0, minimizes reasoning for this deterministic extraction, and validates the response against a JSON schema. The service also splits clear comma-, semicolon-, ampersand-, and "and"-separated grocery names as a safety net. It starts with an 8192-token output budget, retries once with 16384 tokens after a truncated `MAX_TOKENS` response, and returns complete items from the response if the final JSON is cut off.
