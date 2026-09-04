<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\GroceryList;
use App\Services\GeminiGroceryListService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class GroceryListApiController extends Controller
{
    public function __construct(private GeminiGroceryListService $extractor)
    {
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ]);

            $image = $request->file('image');
            $extracted = $this->extractor->extract($image, $request->input('model_id'));

            $list = DB::transaction(function () use ($extracted) {
                $list = GroceryList::create([
                    'user_id' => auth('sanctum')->id(),
                    'status' => 'completed',
                    'language' => 'mixed',
                ]);

                foreach ($extracted['items'] as $item) {
                    $list->items()->create([
                        'extracted_name' => $item['english name'],
                        'normalized_name' => mb_strtolower($item['english name']),
                    ]);
                }

                return $list;
            });

            return ApiResponseType::sendJsonResponse(true, 'Grocery list extracted successfully.', [
                'items' => $extracted['items'],
                'metadata' => array_filter([
                    'list_id' => $list->id,
                    'model' => $extracted['model'],
                    'warning' => $extracted['warning'],
                ], fn ($value) => $value !== null),
            ]);
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to process grocery list image.', [], 500);
        }
    }

    public function index(): JsonResponse
    {
        $lists = GroceryList::with('items')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        $lists->getCollection()->transform(fn (GroceryList $list) => $this->formatList($list));
        return ApiResponseType::sendJsonResponse(true, 'Grocery lists fetched successfully.', $lists);
    }

    public function show(int $id): JsonResponse
    {
        $list = GroceryList::with('items')
            ->where('user_id', auth()->id())->find($id);

        if (!$list) {
            return ApiResponseType::sendJsonResponse(false, 'Grocery list not found.', [], 404);
        }

        return ApiResponseType::sendJsonResponse(true, 'Grocery list fetched successfully.', $this->formatList($list));
    }

    private function formatList(GroceryList $list): array
    {
        return [
            'id' => $list->id,
            'status' => $list->status,
            'language' => $list->language,
            'extracted_text' => $list->extracted_text,
            'rejection_reason' => $list->rejection_reason,
            'created_at' => $list->created_at,
            'items' => $list->items->map(fn ($item) => [
                'id' => $item->id,
                'english name' => $item->extracted_name,
            ])->values()->all(),
        ];
    }
}