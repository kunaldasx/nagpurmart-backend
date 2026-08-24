<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Models\GroceryList;
use App\Models\Product;
use App\Services\GeminiGroceryListService;
use App\Types\Api\ApiResponseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            $extracted = $this->extractor->extract($image);
            $isGroceryList = filter_var($extracted['is_grocery_list'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $imagePath = $image->store('grocery-lists', 'public');

            $list = DB::transaction(function () use ($extracted, $isGroceryList, $imagePath) {
                $list = GroceryList::create([
                    'user_id' => auth()->id(),
                    'image_path' => $imagePath,
                    'status' => $isGroceryList ? 'completed' : 'rejected',
                    'language' => $extracted['language'] ?? 'unknown',
                    'extracted_text' => $extracted['raw_text'] ?? null,
                    'rejection_reason' => $isGroceryList ? null : 'Image does not appear to contain a grocery list.',
                ]);

                if ($isGroceryList) {
                    foreach ($extracted['items'] as $item) {
                        $name = trim((string) ($item['name'] ?? ''));
                        $product = $this->findProduct($name);
                        $list->items()->create([
                            'product_id' => $product?->id,
                            'extracted_name' => $name,
                            'normalized_name' => mb_strtolower($name),
                            'quantity' => is_numeric($item['quantity'] ?? null) ? $item['quantity'] : null,
                            'unit' => isset($item['unit']) ? trim((string) $item['unit']) : null,
                            'confidence' => is_numeric($item['confidence'] ?? null) ? $item['confidence'] : null,
                        ]);
                    }
                }

                return $list;
            });

            $list->load(['items.product' => fn (Builder $query) => $this->productRelations($query)]);

            return ApiResponseType::sendJsonResponse(
                $isGroceryList,
                $isGroceryList ? 'Grocery list extracted successfully.' : 'The image is not a grocery list.',
                $this->formatList($list),
                $isGroceryList ? 200 : 422
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to process grocery list image.', [], 500);
        }
    }

    public function index(): JsonResponse
    {
        $lists = GroceryList::with(['items.product' => fn (Builder $query) => $this->productRelations($query)])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        $lists->getCollection()->transform(fn (GroceryList $list) => $this->formatList($list));
        return ApiResponseType::sendJsonResponse(true, 'Grocery lists fetched successfully.', $lists);
    }

    public function show(int $id): JsonResponse
    {
        $list = GroceryList::with(['items.product' => fn (Builder $query) => $this->productRelations($query)])
            ->where('user_id', auth()->id())->find($id);

        if (!$list) {
            return ApiResponseType::sendJsonResponse(false, 'Grocery list not found.', [], 404);
        }

        return ApiResponseType::sendJsonResponse(true, 'Grocery list fetched successfully.', $this->formatList($list));
    }

    private function findProduct(string $name): ?Product
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $product = Product::query()
            ->where('status', 'active')->where('verification_status', 'approved')
            ->where(function ($query) use ($name) {
                $query->where('title', 'like', "%{$name}%")
                    ->orWhere('tags', 'like', "%{$name}%")
                    ->orWhereHas('category', fn ($category) => $category->where('title', 'like', "%{$name}%"));
            })->first();

        return $product?->load($this->productRelationsArray());
    }

    private function productRelations(Builder $query): Builder
    {
        return $query->with($this->productRelationsArray());
    }

    private function productRelationsArray(): array
    {
        return [
            'category', 'brand', 'seller.user', 'variants.storeProductVariants.store',
            'variants.attributes.attribute', 'variants.attributes.attributeValue',
            'variantAttributes.attribute', 'variantAttributes.attributeValue',
        ];
    }

    private function formatList(GroceryList $list): array
    {
        return [
            'id' => $list->id,
            'status' => $list->status,
            'language' => $list->language,
            'extracted_text' => $list->extracted_text,
            'rejection_reason' => $list->rejection_reason,
            'image_url' => $list->image_path ? Storage::disk('public')->url($list->image_path) : null,
            'created_at' => $list->created_at,
            'items' => $list->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->extracted_name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'confidence' => $item->confidence,
                'product' => $item->product ? (new ProductResource($item->product))->toArray(request()) : null,
            ])->values()->all(),
        ];
    }
}