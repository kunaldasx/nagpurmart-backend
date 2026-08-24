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

The endpoint is temporarily public while frontend authentication is being integrated. If a bearer token is supplied, the list is linked to that user; without one, the extraction is stored as an anonymous record. It validates the file, sends it once to Gemini 2.5 Flash for classification and extraction, matches each extracted item against active approved products, and returns full product details for matched items.

Successful response data includes `id`, `status`, `language`, `extracted_text`, `image_url`, `created_at`, and `items`. Each item includes `name`, `quantity`, `unit`, `confidence`, and `product` (a full product resource or `null` when no match exists).

Non-grocery images return HTTP `422` with `status: rejected`, an empty item list, and `rejection_reason`. Validation errors also return `422`; an unavailable AI provider returns `500`.

## List previous uploads

`GET /user/grocery-lists?per_page=15`

Returns paginated lists belonging only to the authenticated user, including their extracted items and product matches.

## Get one upload

`GET /user/grocery-lists/{id}`

Returns one list owned by the authenticated user, or `404` when it does not exist.

## Configuration

Configure the key and model in Admin Panel > Settings > System. The admin model setting takes precedence; when it is blank, the service uses `GEMINI_MODEL` from the server `.env`, defaulting to `gemini-2.5-flash`. If a configured model returns 404, the service retries once with `gemini-2.5-flash`. The prompt uses JSON mode, temperature 0, and a 900-token output cap; classification and extraction happen in the same request to avoid a second AI call.
