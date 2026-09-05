<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
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
{"items":["Onion"]}
Rules:
- each item must be a plain string; do not return objects
- use familiar Indian household and grocery-catalog names where appropriate: Atta, Maida, Rawa/Suji, Poha, Mota Poha, Besan, Dal, Chana, etc.
- use these canonical catalog mappings when applicable: whole wheat flour -> Atta; refined wheat flour -> Maida; semolina -> Rawa/Suji; thick flattened rice -> Mota Poha; chickpea flour -> Besan
- do not replace a familiar Indian name with an unnecessarily formal translation
- translate or transliterate Hindi/Marathi and handwritten text into a concise searchable grocery name
- read every visible line from top to bottom; each distinct grocery is a separate item, even when multiple groceries appear on one line separated by commas, "and", or semicolons
- preserve visible brands, flavors, varieties, and pack descriptors when they are part of the item name
- do not invent items that are not visible
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
                                    'type' => 'STRING',
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

        $data['items'] = $this->normalizeItems($this->expandCombinedItems($data['items']));

        return ['items' => $data['items'], 'model' => $model, 'warning' => $warning];
    }

    private function normalizeItems(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            $name = is_string($item)
                ? $item
                : (is_array($item) ? ($item['english name'] ?? $item['name'] ?? $item['item'] ?? null) : null);
            if (blank($name)) {
                return null;
            }

            return $this->canonicalizeName((string) $name);
        }, $items)));
    }

    private function canonicalizeName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        $canonicalNames = [
            'refined wheat flour' => 'Maida',
            'refined flour' => 'Maida',
            'all purpose flour' => 'Maida',
            'semolina' => 'Rawa/Suji',
            'sooji' => 'Rawa/Suji',
            'suji' => 'Rawa/Suji',
            'thick flattened rice' => 'Mota Poha',
            'thick poha' => 'Mota Poha',
            'flattened rice' => 'Poha',
            'beaten rice' => 'Poha',
            'whole wheat flour' => 'Atta',
            'wheat flour' => 'Atta',
            'chickpea flour' => 'Besan',
            'gram flour' => 'Besan',
        ];

        return $canonicalNames[strtolower($name)] ?? $name;
    }

    private function expandCombinedItems(array $items): array
    {
        $expanded = [];

        foreach ($items as $item) {
            $name = is_string($item)
                ? $item
                : (is_array($item) ? ($item['english name'] ?? $item['name'] ?? $item['item'] ?? null) : null);
            if (!is_string($name) || !preg_match('/[,;&]|\band\b/i', $name)) {
                $expanded[] = $item;
                continue;
            }

            $names = preg_split('/\s*(?:,|;|&|\band\b)\s*/i', $name, -1, PREG_SPLIT_NO_EMPTY);
            if (count($names) < 2) {
                $expanded[] = $item;
                continue;
            }

            foreach ($names as $singleName) {
                $expanded[] = trim($singleName);
            }
        }

        return array_slice($expanded, 0, self::MAX_ITEMS);
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
            ->connectTimeout(15)
            ->timeout((int) config('services.gemini.timeout', 120))
            ->retry(1, 1000, fn ($exception) => $exception instanceof ConnectionException);
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