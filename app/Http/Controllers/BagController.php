<?php

namespace App\Http\Controllers;

use App\Models\Bag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BagController extends Controller
{
    public function sellerIndex()
    {
        return view('seller.bags.index');
    }

    public function adminIndex()
    {
        return view('admin.bags.index');
    }

    public function index(Request $request): JsonResponse
    {
        $seller = auth()->user()?->seller();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Seller not found.', 'data' => null], 404);
        }

        $query = Bag::query()->where('seller_id', $seller->id)->with('sellerOrder.order:id,order_number');
        $this->applyFilters($query, $request);
        $paginator = $query->orderByDesc('id')->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'success' => true,
            'message' => 'Bags fetched successfully.',
            'data' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'items' => $paginator->getCollection()->map(fn (Bag $bag) => $this->serializeBag($bag))->values(),
            ],
        ]);
    }

    public function adminData(Request $request): JsonResponse
    {
        $query = Bag::query()->with(['seller.user', 'sellerOrder.order:id,order_number']);
        $this->applyFilters($query, $request);
        $paginator = $query->orderByDesc('id')->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'success' => true,
            'message' => 'Bags fetched successfully.',
            'data' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'items' => $paginator->getCollection()->map(function (Bag $bag) {
                    return $this->serializeBag($bag) + [
                        'seller' => [
                            'id' => $bag->seller_id,
                            'name' => $bag->seller?->user?->name,
                        ],
                    ];
                })->values(),
            ],
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $seller = auth()->user()?->seller();
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Seller not found.', 'data' => null], 404);
        }

        $input = $request->input('barcodes');
        if (is_string($input)) {
            $input = preg_split('/[\r\n,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($input) || collect($input)->contains(fn ($barcode) => !is_string($barcode))) {
            return response()->json([
                'success' => false,
                'message' => 'Barcodes must be provided as text or an array of strings.',
                'data' => ['errors' => ['barcodes' => ['Use a string or an array containing only strings.']]],
            ], 422);
        }

        $barcodes = collect($input)->map(fn ($barcode) => trim($barcode))
            ->filter()->unique()->values();

        if ($barcodes->isEmpty() || $barcodes->count() > 1000) {
            return response()->json([
                'success' => false,
                'message' => 'Provide between 1 and 1000 unique bag barcodes.',
                'data' => ['errors' => ['barcodes' => ['Provide between 1 and 1000 unique bag barcodes.']]],
            ], 422);
        }

        $invalid = $barcodes->filter(fn ($barcode) => strlen($barcode) > 255)->values();
        if ($invalid->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Each barcode must be 255 characters or fewer.',
                'data' => ['invalid_barcodes' => $invalid],
            ], 422);
        }

        $existing = Bag::query()->whereIn('barcode', $barcodes)->pluck('barcode');
        $available = $barcodes->diff($existing)->values();
        $now = now();
        $rows = $available->map(fn ($barcode) => [
            'seller_id' => $seller->id,
            'barcode' => $barcode,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($rows)) {
            DB::table('bags')->insertOrIgnore($rows);
        }

        $inserted = Bag::query()->where('seller_id', $seller->id)->whereIn('barcode', $available)->pluck('barcode');
        $duplicates = $barcodes->diff($inserted)->values();

        return response()->json([
            'success' => true,
            'message' => 'Bag barcode import completed.',
            'data' => [
                'created_count' => $inserted->count(),
                'duplicate_count' => $duplicates->count(),
                'created_barcodes' => $inserted->values(),
                'duplicate_barcodes' => $duplicates,
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $seller = auth()->user()?->seller();
        $bag = $seller ? Bag::query()->where('seller_id', $seller->id)->find($id) : null;
        if (!$bag) {
            return response()->json(['success' => false, 'message' => 'Bag not found.', 'data' => null], 404);
        }
        if ($bag->seller_order_id) {
            return response()->json(['success' => false, 'message' => 'An assigned bag cannot be edited.', 'data' => null], 409);
        }

        $request->merge(['barcode' => trim((string) $request->input('barcode'))]);
        $validated = Validator::make($request->all(), [
            'barcode' => ['required', 'string', 'max:255', Rule::unique('bags', 'barcode')->ignore($bag->id)],
        ])->validate();

        $bag->update(['barcode' => trim($validated['barcode'])]);
        return response()->json(['success' => true, 'message' => 'Bag updated successfully.', 'data' => $this->serializeBag($bag->refresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $seller = auth()->user()?->seller();
        $bag = $seller ? Bag::query()->where('seller_id', $seller->id)->find($id) : null;
        if (!$bag) {
            return response()->json(['success' => false, 'message' => 'Bag not found.', 'data' => null], 404);
        }
        if ($bag->seller_order_id) {
            return response()->json(['success' => false, 'message' => 'An assigned bag cannot be deleted.', 'data' => null], 409);
        }

        $bag->delete();
        return response()->json(['success' => true, 'message' => 'Bag deleted successfully.', 'data' => ['id' => $id]]);
    }

    private function applyFilters($query, Request $request): void
    {
        if (in_array($request->input('status'), ['available', 'assigned'], true)) {
            $request->input('status') === 'available'
                ? $query->whereNull('seller_order_id')
                : $query->whereNotNull('seller_order_id');
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('barcode', 'like', '%' . addcslashes($search, '%_') . '%');
        }
    }

    private function serializeBag(Bag $bag): array
    {
        return [
            'id' => $bag->id,
            'barcode' => $bag->barcode,
            'status' => $bag->seller_order_id ? 'assigned' : 'available',
            'seller_order_id' => $bag->seller_order_id,
            'order_number' => $bag->sellerOrder?->order?->order_number,
            'assigned_at' => $bag->assigned_at?->toISOString(),
            'created_at' => $bag->created_at?->toISOString(),
        ];
    }
}