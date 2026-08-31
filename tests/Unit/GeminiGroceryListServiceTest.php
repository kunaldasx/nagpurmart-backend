<?php

use App\Services\GeminiGroceryListService;
use Illuminate\Http\Client\Http;
use Illuminate\Http\UploadedFile;

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
});
