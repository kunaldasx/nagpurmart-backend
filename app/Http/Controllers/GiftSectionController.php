<?php

namespace App\Http\Controllers;

use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\GiftSection\StoreGiftSectionRequest;
use App\Http\Requests\GiftSection\UpdateGiftSectionRequest;
use App\Http\Resources\GiftSectionResource;
use App\Models\GiftSection;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GiftSectionController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', GiftSection::class);
        $giftSection = GiftSection::getInstance();
        $editPermission = $this->hasPermission('gift_section.edit');
        return view($this->panelView('gift-section.index'), compact('giftSection', 'editPermission'));
    }

    public function edit(): View
    {
        $giftSection = GiftSection::getInstance();
        $this->authorize('update', $giftSection);
        return view($this->panelView('gift-section.edit'), compact('giftSection'));
    }

    public function update(UpdateGiftSectionRequest $request): JsonResponse
    {
        $giftSection = GiftSection::getInstance();
        $this->authorize('update', $giftSection);
        
        $data = $request->validated();
        
        // Handle icon image upload
        if ($request->hasFile('icon_image')) {
            $giftSection->clearMediaCollection(SpatieMediaCollectionName::GIFT_SECTION_ICON());
            $giftSection->addMediaFromRequest('icon_image')
                ->toMediaCollection(SpatieMediaCollectionName::GIFT_SECTION_ICON());
            $data['icon_image'] = $giftSection->getFirstMediaUrl(SpatieMediaCollectionName::GIFT_SECTION_ICON());
        }
        
        $giftSection->update([
            'heading' => $data['heading'],
            'sub_heading' => $data['sub_heading'],
            'bg_color' => $data['bg_color'],
            'font_color' => $data['font_color'],
        ]);

        return ApiResponseType::sendJsonResponse(
            true, 
            'Gift section updated successfully.',
            new GiftSectionResource($giftSection)
        );
    }

    public function show(): JsonResponse
    {
        $giftSection = GiftSection::getInstance();
        $this->authorize('view', $giftSection);
        return ApiResponseType::sendJsonResponse(
            true,
            'Gift section retrieved successfully.',
            new GiftSectionResource($giftSection)
        );
    }
}
