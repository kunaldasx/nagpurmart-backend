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
    private const FALLBACK_MODEL = 'gemini-3.5-flash';
    private const MAX_ITEMS = 50;

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
- translate Hindi/Marathi and handwritten text to standard English grocery names
- read every visible line from top to bottom; each line is a separate item unless it is clearly a continuation
- transliterate or translate Hindi/Marathi names, but do not invent items that are not visible
- preserve a clearly written quantity and unit; if either is missing or unclear, use qty 1 and unit pc
- unit must be one of: kg, g, l, ml, pc, bunch
- ignore crossed-out text, prices, headings, phone numbers, and non-grocery notes
- if image is not a grocery list, return {"items":[]}
- no markdown, no code fences, no extra text
- max 50 items
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
                    'maxOutputTokens' => 8192,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'items' => [
                                'type' => 'ARRAY',
                                'maxItems' => self::MAX_ITEMS,
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        'name' => ['type' => 'STRING'],
                                        'qty' => ['type' => 'NUMBER'],
                                        'unit' => ['type' => 'STRING'],
                                    ],
                                    'required' => ['name', 'qty', 'unit'],
                                ],
                            ],
                        ],
                        'required' => ['items'],
                    ],
                ],
        ];

        $configuredModel = Setting::find('system')?->value['geminiModel'] ?? null;
        $defaultModel = config('services.gemini.model', 'gemini-3.5-flash');
        $model = $requestedModel ?: (filled($configuredModel) ? $configuredModel : $defaultModel);
        $warning = null;
        if (!in_array($model, self::ALLOWED_MODELS, true)) {
            $warning = 'Invalid model_id; fell back to gemini-3.5-flash.';
            $model = self::FALLBACK_MODEL;
        }
        $payload['generationConfig']['thinkingConfig'] = $this->thinkingConfigForModel($model);
        $response = $this->client($apiKey)->post($this->endpoint($model), $payload);

        if ($response->status() === 404 && $model !== self::FALLBACK_MODEL) {
            $warning = 'Selected model was unavailable; fell back to gemini-3.5-flash.';
            $model = self::FALLBACK_MODEL;
            $payload['generationConfig']['thinkingConfig'] = $this->thinkingConfigForModel($model);
            $response = $this->client($apiKey)->post($this->endpoint($model), $payload);
        }

        $response->throw();

        $data = $this->extractJsonPayload($response);
        if ((!is_array($data) || !isset($data['items']) || !is_array($data['items'])) && $response->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            Log::warning('Gemini hit MAX_TOKENS while generating grocery JSON; retrying.', [
                'model' => $model,
                'status' => $response->status(),
                'finish_reason' => $response->json('candidates.0.finishReason'),
                'candidate_tokens' => $response->json('usageMetadata.candidatesTokenCount'),
                'thought_tokens' => $response->json('usageMetadata.thoughtsTokenCount'),
            ]);

            $payload['generationConfig']['maxOutputTokens'] = 16384;
            $response = $this->client($apiKey)->post($this->endpoint($model), $payload);
            $response->throw();
            $data = $this->extractJsonPayload($response);
        }

        if ((!is_array($data) || !isset($data['items']) || !is_array($data['items']))
            && $response->json('candidates.0.finishReason') === 'MAX_TOKENS'
            && $model !== self::FALLBACK_MODEL
        ) {
            Log::warning('Gemini still returned MAX_TOKENS; retrying with the configured fallback model.', [
                'model' => $model,
                'status' => $response->status(),
                'finish_reason' => $response->json('candidates.0.finishReason'),
            ]);

            $warning = 'Selected model exceeded its output budget; fell back to gemini-3.5-flash.';
            $model = self::FALLBACK_MODEL;
            $payload['generationConfig']['thinkingConfig'] = $this->thinkingConfigForModel($model);
            $payload['generationConfig']['maxOutputTokens'] = 8192;
            $response = $this->client($apiKey)->post($this->endpoint($model), $payload);
            $response->throw();
            $data = $this->extractJsonPayload($response);
        }

        if ((!is_array($data) || !isset($data['items']) || !is_array($data['items']))
            && $response->json('candidates.0.finishReason') === 'MAX_TOKENS'
        ) {
            $partialItems = $this->extractCompletedItems($response);
            if ($partialItems !== []) {
                $warning = 'Gemini response was truncated; returned the completed items it produced.';
                $data = ['items' => $partialItems];
            }
        }

        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            Log::error('Gemini returned an invalid grocery list response.', [
                'model' => $model,
                'status' => $response->status(),
                'finish_reason' => $response->json('candidates.0.finishReason'),
                'candidate_tokens' => $response->json('usageMetadata.candidatesTokenCount'),
                'thought_tokens' => $response->json('usageMetadata.thoughtsTokenCount'),
                'parsed_value' => $data,
            ]);

            throw new RuntimeException('Gemini returned an invalid grocery list response.');
        }

        $data['items'] = $this->normalizeItems(array_slice($data['items'], 0, self::MAX_ITEMS));

        return ['items' => $data['items'], 'model' => $model, 'warning' => $warning];
    }

    private function normalizeItems(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
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
        }, $items)));
    }

    private function extractJsonPayload(
        \Illuminate\Http\Client\Response $response
    ): ?array {
        $text = $this->responseText($response);
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

    private function extractCompletedItems(
        \Illuminate\Http\Client\Response $response
    ): array {
        preg_match_all('/\{[^{}]*\}/s', $this->responseText($response), $matches);
        $items = [];

        foreach ($matches[0] as $candidate) {
            $item = json_decode($candidate, true);
            if (is_array($item) && (isset($item['name']) || isset($item['english name']) || isset($item['item']))) {
                $items[] = $item;
            }
        }

        return $this->normalizeItems(array_slice($items, 0, self::MAX_ITEMS));
    }

    private function responseText(\Illuminate\Http\Client\Response $response): string
    {
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

        return $text;
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

    private function thinkingConfigForModel(string $model): array
    {
        return str_starts_with($model, 'gemini-3.')
            ? ['thinkingLevel' => 'MINIMAL']
            : ['thinkingBudget' => 0];
    }

    private function endpoint(string $model): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
    }
}