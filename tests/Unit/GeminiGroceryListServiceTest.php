<?php

use App\Services\GeminiGroceryListService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('parses markdown-wrapped json from Gemini into grocery items', function () {
    config(['services.gemini.api_key' => 'test-key']);

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => <<<'JSON'
```json
{"items":[{"name":"Tomato"},{"name":"Milk"},{"name":"Onion"}]}
```
JSON,
                    ]],
                ],
            ]],
        ]),
    ]);

    $service = app(GeminiGroceryListService::class);
    $result = $service->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toHaveCount(3)
        ->and($result['items'])->toBe(['Tomato', 'Milk', 'Onion']);

    Http::assertSent(function ($request) {
        $generationConfig = $request->data()['generationConfig'];

        return $generationConfig['maxOutputTokens'] === 8192
            && $generationConfig['thinkingConfig']['thinkingLevel'] === 'MINIMAL'
            && $generationConfig['responseSchema']['properties']['items']['type'] === 'ARRAY'
            && $generationConfig['responseSchema']['properties']['items']['maxItems'] === 50
            && $generationConfig['responseSchema']['properties']['items']['items']['type'] === 'STRING';
    });
});

it('retries truncated Gemini JSON with a larger budget', function () {
    config(['services.gemini.api_key' => 'test-key']);

    Http::fakeSequence()
        ->push([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"items":[{"name":"Rice"']]],
                'finishReason' => 'MAX_TOKENS',
            ]],
            'usageMetadata' => ['thoughtsTokenCount' => 980, 'candidatesTokenCount' => 28],
        ])
        ->push([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"items":[{"name":"Rice"}]}']]],
            ]],
        ]);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toMatchArray(['Rice']);

    Http::assertSentCount(2);
    Http::assertSent(function ($request) {
        return $request->data()['generationConfig']['maxOutputTokens'] === 16384;
    });
});

it('returns completed items when Gemini truncates the final response', function () {
    config(['services.gemini.api_key' => 'test-key']);

    $truncated = [
        'candidates' => [[
            'content' => ['parts' => [[
                'text' => '{"items":[{"name":"Rice"},{"name":"Dal"},{"name":"Oil"',
            ]]],
            'finishReason' => 'MAX_TOKENS',
        ]],
    ];

    Http::fakeSequence()->push($truncated)->push($truncated);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toMatchArray([
        'Rice',
        'Dal',
    ])->and($result['warning'])->toContain('truncated');

    Http::assertSentCount(2);
});

it('splits distinct groceries combined on one line', function () {
    config(['services.gemini.api_key' => 'test-key']);

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => '{"items":[{"name":"Cashew, Almond, Raisin, Foxnuts"},{"name":"Tomato Sauce, Red Chili Sauce"}]}',
                ]]],
            ]],
        ]),
    ]);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toBe([
        'Cashew',
        'Almond',
        'Raisin',
        'Foxnuts',
        'Tomato Sauce',
        'Red Chili Sauce',
    ]);
});

it('normalizes formal translations to Indian catalog names', function () {
    config(['services.gemini.api_key' => 'test-key']);

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => '{"items":["Refined Wheat Flour","Semolina","Thick Flattened Rice","Whole Wheat Flour"]}',
                ]]],
            ]],
        ]),
    ]);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toBe(['Maida', 'Rawa/Suji', 'Mota Poha', 'Atta']);
});
