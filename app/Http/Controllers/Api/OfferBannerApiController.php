<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferBanner;
use Illuminate\Http\Request;

class OfferBannerApiController extends Controller
{
    public function index(Request $request)
    {
        $scopeType = $request->get('scope_type');
        $scopeId = $request->get('scope_id');

        $query = OfferBanner::query()->with(['items', 'items.product', 'items.category']);
        if ($scopeType) $query->where('scope_type', $scopeType);
        if ($scopeId) $query->where('scope_id', $scopeId);
        $query->where('visibility_status', 'published');

        $banners = $query->orderBy('display_order', 'asc')->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'title' => $b->title,
                'template_code' => $b->template_code,
                'position' => $b->position,
                'scope_type' => $b->scope_type,
                'scope_id' => $b->scope_id,
                'display_order' => $b->display_order,
                'images' => $b->getMedia('offer_banner_images')->map(fn($m) => $m->getFullUrl()),
                'items' => $b->items->map(fn($i) => [
                    'id' => $i->id,
                    'title' => $i->title,
                    'subtitle' => $i->subtitle,
                    'type' => $i->item_type,
                    'item_id' => $i->item_id,
                ]),
            ];
        });

        return response()->json(['success' => true, 'data' => $banners]);
    }
}
