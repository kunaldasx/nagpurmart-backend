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

The endpoint is temporarily public while frontend authentication is being integrated. If a bearer token is supplied, the list is linked to that user; without one, the extraction is stored as an anonymous record. It validates the file, sends it once to Gemini 2.5 Flash-Lite for classification and extraction, matches each extracted item against active approved products, and returns full product details for matched items.

Successful response data includes `id`, `status`, `language`, `extracted_text`, `image_url`, `created_at`, and `items`. Each item includes `name`, `quantity`, `unit`, `confidence`, and `product` (a full product resource or `null` when no match exists).

Non-grocery images return HTTP `422` with `status: rejected`, an empty item list, and `rejection_reason`. Validation errors also return `422`; an unavailable AI provider returns `500`.

## List previous uploads

`GET /user/grocery-lists?per_page=15`

Returns paginated lists belonging only to the authenticated user, including their extracted items and product matches.

## Get one upload

`GET /user/grocery-lists/{id}`

Returns one list owned by the authenticated user, or `404` when it does not exist.

## Configuration

Configure the key in Admin Panel > Settings > System > Gemini API key. The service uses that value first; when it is blank, it falls back to `GEMINI_API_KEY` from the server `.env`. Optional environment settings are `GEMINI_MODEL` (defaults to `gemini-2.5-flash-lite`) and `GEMINI_TIMEOUT` (defaults to 30 seconds). The prompt uses JSON mode, temperature 0, and a 900-token output cap; classification and extraction happen in the same request to avoid a second AI call. Flash-Lite is the cost-effective default for this OCR/classification workload; use full Flash only when higher image reasoning quality justifies the added cost.
