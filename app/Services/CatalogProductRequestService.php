<?php

namespace App\Services;

use App\Models\CatalogProductRequest;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CatalogProductRequestService
{
    public function submit(?Seller $seller, User $user, array $data): CatalogProductRequest
    {
        $normalizedName = mb_strtolower(trim($data['product_name']));

        $alreadyInCatalog = Product::query()
            ->whereRaw('LOWER(title) = ?', [$normalizedName])
            ->exists();

        if ($alreadyInCatalog) {
            throw ValidationException::withMessages([
                'product_name' => 'This product already exists in the catalog.',
            ]);
        }

        $alreadyRequested = CatalogProductRequest::query()
            ->whereRaw('LOWER(product_name) = ?', [$normalizedName])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($alreadyRequested) {
            throw ValidationException::withMessages([
                'product_name' => 'A request for this product is already being handled.',
            ]);
        }

        return CatalogProductRequest::create([
            ...$data,
            'seller_id' => $seller?->id,
            'user_id' => $user->id,
        ]);
    }
}