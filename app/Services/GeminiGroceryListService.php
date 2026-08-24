<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiGroceryListService
{
    public function extract(UploadedFile $image): array
    {
        $apiKey = Setting::find('system')?->value['geminiApiKey'] ?? config('services.gemini.api_key');
        if (!$apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $prompt = <<<'PROMPT'
You extract grocery lists from images. Return ONLY valid JSON matching this shape:
{"is_grocery_list":true,"language":"en|hi|mr|mixed|unknown","raw_text":"...","items":[{"name":"canonical grocery product name","quantity":null,"unit":null,"confidence":0.0}]}
If the image is not primarily a handwritten or printed grocery list, return is_grocery_list false, raw_text empty, and items [].
Read English, Hindi, Marathi, and mixtures. Preserve the original item meaning in raw_text, but use a concise canonical name in items.name. Ignore prices, checkmarks, headings, phone numbers, and non-product text. Do not invent missing quantities. Maximum 30 items. Confidence must be between 0 and 1.
PROMPT;

        $response = $this->client($apiKey)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . config('services.gemini.model') . ':generateContent',
            [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => [
                            'mime_type' => $image->getMimeType(),
                            'data' => base64_encode(file_get_contents($image->getRealPath())),
                        ]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => 900,
                    'responseMimeType' => 'application/json',
                ],
            ]
        )->throw();

        $text = $response->json('candidates.0.content.parts.0.text');
        $data = json_decode(trim((string) $text), true);
        if (!is_array($data) || !isset($data['is_grocery_list'], $data['items']) || !is_array($data['items'])) {
            throw new RuntimeException('Gemini returned an invalid grocery list response.');
        }

        $data['items'] = array_values(array_filter(array_slice($data['items'], 0, 30), function ($item) {
            return is_array($item) && !empty(trim((string) ($item['name'] ?? '')));
        }));

        return $data;
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::acceptJson()
            ->withOptions(['query' => ['key' => $apiKey]])
            ->timeout((int) config('services.gemini.timeout', 30));
    }
}