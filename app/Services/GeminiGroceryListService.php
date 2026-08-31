<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiGroceryListService
{
    public const ALLOWED_MODELS = [
        'gemini-3.5-flash-lite',
        'gemini-3.5-flash',
        'gemini-3.1-pro-preview',
        'gemini-2.5-flash',
    ];

    public function extract(UploadedFile $image, ?string $requestedModel = null): array
    {
        $configuredApiKey = Setting::find('system')?->value['geminiApiKey'] ?? null;
        $apiKey = filled($configuredApiKey) ? $configuredApiKey : config('services.gemini.api_key');
        if (!$apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $prompt = <<<'PROMPT'
Return ONLY compact JSON with this exact structure:
{"items":[{"name":"Onion","qty":1,"unit":"kg"}]}
Rules:
- only keys allowed in each item: name, qty, unit
- translate Hindi/Marathi and handwritten text to standard English names
- qty must be a number; if missing, use 1
- unit must be one of: kg, g, l, ml, pc, bunch
- ignore crossed-out text and non-grocery notes
- if image is not a grocery list, return {"items":[]}
- no markdown, no code fences, no extra text
- max 30 items
PROMPT;

        $payload = [
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
                    'maxOutputTokens' => 1024,
                    'responseMimeType' => 'application/json',
                ],
        ];

        $configuredModel = Setting::find('system')?->value['geminiModel'] ?? null;
        $defaultModel = config('services.gemini.model', 'gemini-3.5-flash');
        $model = $requestedModel ?: (filled($configuredModel) ? $configuredModel : $defaultModel);
        $warning = null;
        if (!in_array($model, self::ALLOWED_MODELS, true)) {
            $warning = 'Invalid model_id; fell back to gemini-3.5-flash.';
            $model = 'gemini-3.5-flash';
        }
        $response = $this->client($apiKey)->post($this->endpoint($model), $payload);

        if ($response->status() === 404 && $model !== 'gemini-2.5-flash') {
            $warning = 'Selected model was unavailable; fell back to gemini-2.5-flash.';
            $model = 'gemini-2.5-flash';
            $response = $this->client($apiKey)->post($this->endpoint($model), $payload);
        }

        $response->throw();

        $data = $this->extractJsonPayload($response);
        if ((!is_array($data) || !isset($data['items']) || !is_array($data['items'])) && $response->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            Log::warning('Gemini hit MAX_TOKENS while generating grocery JSON; retrying with a larger output budget.', [
                'model' => $model,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            $payload['generationConfig']['maxOutputTokens'] = 2048;
            $response = $this->client($apiKey)->post($this->endpoint($model), $payload);
            $response->throw();
            $data = $this->extractJsonPayload($response);
        }

        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            Log::error('Gemini returned an invalid grocery list response.', [
                'model' => $model,
                'status' => $response->status(),
                'response_body' => $response->body(),
                'parsed_value' => $data,
            ]);

            throw new RuntimeException('Gemini returned an invalid grocery list response.');
        }

        $data['items'] = array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $name = $item['english name'] ?? $item['name'] ?? $item['item'] ?? null;
            if (blank($name)) {
                return null;
            }

            $unit = strtolower(trim((string) ($item['unit'] ?? 'pc')));
            if (!in_array($unit, ['kg', 'g', 'l', 'ml', 'pc', 'bunch'], true)) {
                $unit = 'pc';
            }

            $qty = $item['qty'] ?? 1;
            if ($qty === '' || $qty === null) {
                $qty = 1;
            }

            return [
                'english name' => trim((string) $name),
                'qty' => is_numeric($qty) && (float) $qty > 0 ? (float) $qty : 1,
                'unit' => $unit,
            ];
        }, array_slice($data['items'], 0, 30))));

        return ['items' => $data['items'], 'model' => $model, 'warning' => $warning];
    }

    private function extractJsonPayload(
        \Illuminate\Http\Client\Response $response
    ): ?array {
        $parts = $response->json('candidates.0.content.parts');
        $text = '';

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }

        if ($text === '') {
            $text = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
        }

        $decoded = $this->decodeJsonString($text);
        if (is_array($decoded)) {
            return $decoded;
        }

        $raw = $response->json();
        if (is_array($raw)) {
            $decoded = $this->decodeJsonString((string) json_encode($raw));
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function decodeJsonString(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        foreach (['/```(?:json)?\s*(\{.*?\})\s*```/is', '/```(?:json)?\s*(\[.*?\])\s*```/is', '/(\{.*\})/s', '/(\[.*\])/s'] as $pattern) {
            if (preg_match($pattern, $trimmed, $matches)) {
                $trimmed = $matches[1];
                break;
            }
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            return $decoded;
        }

        if (is_array($decoded) && array_is_list($decoded)) {
            return ['items' => $decoded];
        }

        return null;
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::acceptJson()
            ->withOptions(['query' => ['key' => $apiKey]])
            ->timeout((int) config('services.gemini.timeout', 30));
    }

    private function endpoint(string $model): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
    }
}