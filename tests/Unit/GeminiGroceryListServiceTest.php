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
{"items":[{"english name":"Tomato","qty":"1","unit":"kg"},{"english name":"Milk","qty":"0.5","unit":"L"},{"english name":"Onion","qty":"","unit":""}]}
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
        ->and($result['items'][0])->toMatchArray([
            'english name' => 'Tomato',
            'qty' => 1.0,
            'unit' => 'kg',
        ])
        ->and($result['items'][1])->toMatchArray([
            'english name' => 'Milk',
            'qty' => 0.5,
            'unit' => 'l',
        ])
        ->and($result['items'][2])->toMatchArray([
            'english name' => 'Onion',
            'qty' => 1,
            'unit' => 'pc',
        ]);

    Http::assertSent(function ($request) {
        $generationConfig = $request->data()['generationConfig'];

        return $generationConfig['maxOutputTokens'] === 8192
            && $generationConfig['thinkingConfig']['thinkingLevel'] === 'MINIMAL'
            && $generationConfig['responseSchema']['properties']['items']['type'] === 'ARRAY'
            && $generationConfig['responseSchema']['properties']['items']['maxItems'] === 50;
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
                'content' => ['parts' => [['text' => '{"items":[{"name":"Rice","qty":1,"unit":"kg"}]}']]],
            ]],
        ]);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toMatchArray([[
        'english name' => 'Rice',
        'qty' => 1.0,
        'unit' => 'kg',
    ]]);

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
                'text' => '{"items":[{"name":"Rice","qty":1,"unit":"kg"},{"name":"Dal","qty":2,"unit":"kg"},{"name":"Oil"',
            ]]],
            'finishReason' => 'MAX_TOKENS',
        ]],
    ];

    Http::fakeSequence()->push($truncated)->push($truncated);

    $result = app(GeminiGroceryListService::class)
        ->extract(UploadedFile::fake()->image('grocery.png', 640, 480));

    expect($result['items'])->toMatchArray([
        ['english name' => 'Rice', 'qty' => 1.0, 'unit' => 'kg'],
        ['english name' => 'Dal', 'qty' => 2.0, 'unit' => 'kg'],
    ])->and($result['warning'])->toContain('truncated');

    Http::assertSentCount(2);
});
