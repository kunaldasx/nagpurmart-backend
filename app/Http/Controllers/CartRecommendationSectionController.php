<?php

namespace App\Http\Controllers;

use App\Models\CartRecommendationSection;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartRecommendationSectionController extends Controller
{
    public function index(): View
    {
        return view('admin.cart-recommendations.index', [
            'sections' => CartRecommendationSection::with('products')->ordered()->get(),
            'products' => Product::query()->select('id', 'title')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $section = CartRecommendationSection::create($data);
        $this->syncProducts($section, $request->input('products', []));

        return back()->with('success', 'Cart recommendation section created successfully.');
    }

    public function update(Request $request, CartRecommendationSection $section): RedirectResponse
    {
        $section->update($this->validated($request));
        $this->syncProducts($section, $request->input('products', []));

        return back()->with('success', 'Cart recommendation section updated successfully.');
    }

    public function destroy(CartRecommendationSection $section): RedirectResponse
    {
        $section->delete();
        return back()->with('success', 'Cart recommendation section deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'is_tabular' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'distinct', 'exists:products,id'],
        ]) + [
            'is_tabular' => $request->boolean('is_tabular'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }

    private function syncProducts(CartRecommendationSection $section, array $productIds): void
    {
        $section->products()->sync(collect($productIds)->values()->mapWithKeys(
            fn ($productId, $index) => [$productId => ['sort_order' => $index]]
        )->all());
    }
}
