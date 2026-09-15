<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferBanner;
use App\Models\OfferBannerTemplate;
use Illuminate\Http\Request;

class OfferBannerApiController extends Controller
{
    public function index(Request $request)
    {
        $scopeType = $request->get('scope_type');
        $scopeId = $request->get('scope_id');
        $position = $request->get('position');

        $query = OfferBanner::query()->with(['items', 'items.product', 'items.category']);
        if ($scopeType) $query->where('scope_type', $scopeType);
        if ($scopeId) $query->where('scope_id', $scopeId);
        if ($position) {
            $request->validate(['position' => 'in:top,carousel']);
            $query->where('position', $position);
        }
        $query->where('visibility_status', 'published');

        $banners = $query->orderBy('display_order', 'asc')->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'title' => $b->title,
                'template_code' => $b->template_code,
                'template' => $this->template($b->template_code),
                'background_color' => $b->background_color,
                'font_color' => $b->font_color,
                'position' => $b->position,
                'scope_type' => $b->scope_type,
                'scope_id' => $b->scope_id,
                'display_order' => $b->display_order,
                'metadata' => $b->metadata ?? [],
                'images' => $b->getMedia('offer_banner_images')->map(fn($m) => $m->getFullUrl()),
                'items' => $b->items->map(fn($i) => [
                    'id' => $i->id,
                    'title' => $i->title,
                    'subtitle' => $i->subtitle,
                    'type' => $i->item_type,
                    'item_id' => $i->item_id,
                    'metadata' => $i->metadata ?? [],
                    'item' => $this->resolvedItem($i),
                ]),
            ];
        });

        return response()->json(['success' => true, 'data' => $banners]);
    }

    private function template(string $code): ?array
    {
        $template = OfferBannerTemplate::where('code', $code)->first();
        return $template ? [
            'code' => $template->code,
            'name' => $template->name,
            'preview_url' => $template->preview_url,
        ] : null;
    }

    private function resolvedItem($item): ?array
    {
        $model = $item->item_type === 'category' ? $item->category : $item->product;
        if (!$model) return null;
        return [
            'id' => $model->id,
            'title' => $model->title,
            'slug' => $model->slug,
            'image' => $model->image ?? $model->icon ?? null,
        ];
    }
}
