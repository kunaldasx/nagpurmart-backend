<?php

namespace App\Services;

use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Enums\Product\ProductVideoTypeEnum;
use App\Events\Product\ProductStatusAfterUpdate;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\CustomProductSection;
use App\Models\CustomProductField;
use App\Models\StoreProductVariant;
use App\Enums\SpatieMediaCollectionName;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Build payloads for a single product group parsed from CSV bulk upload.
     * This centralizes complex mapping logic out of the controller.
     *
     * @param int $sellerId
     * @param array $normalizedHeaders Lowercased/trimmed CSV headers
     * @param array $rows All CSV rows for a single product (same handle)
     * @return array [basePayload, variantsJson, pricing]
     */
    public function buildBulkUploadPayload(int $sellerId, array $normalizedHeaders, array $rows): array
    {
        $firstRow = $rows[0];
        $type = strtolower(trim((string)($firstRow['type'] ?? '')));
        $title = trim((string)($firstRow['title'] ?? ''));

        // Base payload
        $basePayload = [
            'seller_id' => $firstRow['seller_id'] ?? $sellerId,
            'category_id' => (int)($firstRow['category_id'] ?? 0),
            'brand_id' => ($firstRow['brand_id'] ?? '') !== '' ? (int)$firstRow['brand_id'] : null,
            'title' => $title,
            'type' => $type,
            'image_fit' => $firstRow['image_fit'] ?? 'cover',
            // main image URL support in bulk upload (optional)
            'image_src' => isset($firstRow['image_src']) ? trim((string)$firstRow['image_src']) : null,
            'short_description' => $firstRow['short_description'] ?? '',
            'description' => $firstRow['description'] ?? '',
            'base_prep_time' => ($firstRow['base_prep_time'] ?? '') !== '' ? (int)$firstRow['base_prep_time'] : 0,
            'minimum_order_quantity' => ($firstRow['minimum_order_quantity'] ?? '') !== '' ? (int)$firstRow['minimum_order_quantity'] : 1,
            'quantity_step_size' => ($firstRow['quantity_step_size'] ?? '') !== '' ? (int)$firstRow['quantity_step_size'] : 1,
            'total_allowed_quantity' => ($firstRow['total_allowed_quantity'] ?? '') !== '' ? (int)$firstRow['total_allowed_quantity'] : null,
            'is_returnable' => (string)($firstRow['is_returnable'] ?? 0),
            'returnable_days' => ($firstRow['returnable_days'] ?? '') !== '' ? (int)$firstRow['returnable_days'] : null,
            'is_cancelable' => (string)($firstRow['is_cancelable'] ?? 0),
            'cancelable_till' => $firstRow['cancelable_till'] ?? null,
            'is_attachment_required' => (string)($firstRow['is_attachment_required'] ?? 0),
            'featured' => (string)($firstRow['featured'] ?? 0),
            'requires_otp' => (string)($firstRow['requires_otp'] ?? 0),
            'video_type' => $firstRow['video_type'] ?? null,
            'video_link' => $firstRow['video_link'] ?? null,
            'warranty_period' => $firstRow['warranty_period'] ?? null,
            'guarantee_period' => $firstRow['guarantee_period'] ?? null,
            'made_in' => $firstRow['made_in'] ?? null,
            'hsn_code' => $firstRow['hsn_code'] ?? null,
            'tags' => isset($firstRow['tags']) && $firstRow['tags'] !== '' ? array_map('trim', explode(',', $firstRow['tags'])) : [],
        ];

        // Custom fields: new comma-separated columns, with legacy fallback
        $customFields = [];
        $titlesRaw = $firstRow['custom_fields_title'] ?? '';
        $valuesRaw = $firstRow['custom_fields_value'] ?? '';
        if (($titlesRaw !== null && $titlesRaw !== '') || ($valuesRaw !== null && $valuesRaw !== '')) {
            $titles = array_map('trim', explode(',', (string)$titlesRaw));
            $values = array_map('trim', explode(',', (string)$valuesRaw));
            $max = max(count($titles), count($values));
            for ($i = 0; $i < $max; $i++) {
                $label = $titles[$i] ?? '';
                $value = $values[$i] ?? '';
                if ($label !== '' && $value !== '') {
                    $customFields[$label] = $value;
                }
            }
        } else {
            foreach ($normalizedHeaders as $h) {
                $label = '';
                if (str_starts_with($h, 'custom:')) {
                    $label = trim(substr($h, strlen('custom:')));
                    $label = $label !== '' ? ucwords($label) : '';
                } elseif (str_starts_with($h, 'custom_')) {
                    $label = trim(substr($h, strlen('custom_')));
                    $label = $label !== '' ? ucwords(str_replace('_', ' ', $label)) : '';
                }
                if ($label !== '') {
                    $val = $firstRow[$h] ?? null;
                    if ($val !== null && $val !== '') {
                        $customFields[$label] = $val;
                    }
                }
            }
        }
        if (!empty($customFields)) {
            $basePayload['custom_fields'] = $customFields;
        }

        // Variant bundling by option values
        $variantMap = [];
        foreach ($rows as $r) {
            $opt1Name = trim((string)($r['option1_name'] ?? ''));
            $opt1Val = trim((string)($r['option1_value'] ?? ''));
            $opt2Name = trim((string)($r['option2_name'] ?? ''));
            $opt2Val = trim((string)($r['option2_value'] ?? ''));
            $opt3Name = trim((string)($r['option3_name'] ?? ''));
            $opt3Val = trim((string)($r['option3_value'] ?? ''));

            $key = implode('|', [$opt1Val, $opt2Val, $opt3Val]);
            if (!isset($variantMap[$key])) {
                $parts = [];
                if ($opt1Name && $opt1Val) {
                    $parts[] = "$opt1Val";
                }
                if ($opt2Name && $opt2Val) {
                    $parts[] = "$opt2Val";
                }
                if ($opt3Name && $opt3Val) {
                    $parts[] = "$opt3Val";
                }
                $variantTitle = count($parts) ? implode(' / ', $parts) : 'Default Title';
                $variantMap[$key] = [
                    'row' => $r,
                    'title' => $variantTitle,
                    'stores' => [],
                ];
            }
            $variantMap[$key]['stores'][] = [
                'store_id' => ($r['store_id'] ?? '') !== '' ? (int)$r['store_id'] : null,
                'sku' => $r['variant_sku'] ?? null,
                'price' => ($r['variant_price'] ?? '') !== '' ? (float)$r['variant_price'] : null,
                'special_price' => ($r['variant_special_price'] ?? '') !== '' ? (float)$r['variant_special_price'] : null,
                'cost' => ($r['variant_cost'] ?? '') !== '' ? (float)$r['variant_cost'] : null,
                'stock' => ($r['variant_stock'] ?? '') !== '' ? (int)$r['variant_stock'] : 0,
            ];
        }

        // Assemble pricing and variants_json
        $pricing = [];
        $variantsJson = [];
        $generatedId = 1000;
        $defaultMarked = false;

        foreach ($variantMap as $bundle) {
            $r = $bundle['row'];
            $variantId = $generatedId++;
            $variantIsDefault = strtolower(trim((string)($r['variant_is_default'] ?? '')));
            $isDefault = $variantIsDefault === 'on' || $variantIsDefault === '1' || (!$defaultMarked && $type === ProductTypeEnum::SIMPLE->value);
            if ($isDefault) {
                $defaultMarked = true;
            }

            // Resolve variant attributes from options
            $resolvedAttributes = [];
            $optionPairs = [
                ['name' => trim((string)($r['option1_name'] ?? '')), 'value' => trim((string)($r['option1_value'] ?? ''))],
                ['name' => trim((string)($r['option2_name'] ?? '')), 'value' => trim((string)($r['option2_value'] ?? ''))],
                ['name' => trim((string)($r['option3_name'] ?? '')), 'value' => trim((string)($r['option3_value'] ?? ''))],
            ];
            foreach ($optionPairs as $pair) {
                if ($pair['name'] !== '' && $pair['value'] !== '') {
                    $av = $this->resolveOrCreateAttributeAndValue($sellerId, $pair['name'], $pair['value']);
                    if ($av) {
                        $resolvedAttributes[] = $av;
                    }
                }
            }

            $variantsJson[] = [
                'id' => $variantId,
                'title' => $bundle['title'],
                'attributes' => array_map(function ($av) {
                    return [
                        'attribute_id' => $av['attribute_id'],
                        'value_id' => $av['value_id'],
                    ];
                }, $resolvedAttributes),
                'barcode' => $r['variant_barcode'] ?? null,
                'weight' => ($r['variant_weight'] ?? '') !== '' ? (float)$r['variant_weight'] : null,
                'height' => ($r['variant_height'] ?? '') !== '' ? (float)$r['variant_height'] : null,
                'length' => ($r['variant_length'] ?? '') !== '' ? (float)$r['variant_length'] : null,
                'breadth' => ($r['variant_breadth'] ?? '') !== '' ? (float)$r['variant_breadth'] : null,
                'is_default' => $isDefault ? 'on' : 'off',
            ];
            // Build pricing following existing structure in controller/service
            foreach ($bundle['stores'] as $storeRow) {
                if (!$storeRow['store_id']) {
                    continue;
                }
                // Validate store id exists AND belongs to the current seller; if not, surface a clear error message
                $storeBelongsToSeller = Store::where('id', $storeRow['store_id'])
                    ->where('seller_id', $sellerId)
                    ->exists();
                if (!$storeBelongsToSeller) {
                    throw new \Exception(__('labels.invalid_store_for_seller'));
                }
                if (!is_numeric($storeRow['price']) || !is_numeric($storeRow['cost'])) {
                    continue;
                }
                $sp = $storeRow['special_price'];
                if ($sp !== null && $sp !== '' && $sp >= $storeRow['price']) {
                    $sp = $storeRow['price'];
                }
                if ($type === ProductTypeEnum::SIMPLE->value) {
                    $pricing['store_pricing'][] = [
                        'store_id' => $storeRow['store_id'],
                        'price' => $storeRow['price'],
                        'special_price' => $sp,
                        'cost' => $storeRow['cost'],
                        'stock' => $storeRow['stock'] ?? 0,
                        'sku' => $storeRow['sku'] ?? null,
                    ];
                } else {
                    $pricing['variant_pricing'][] = [
                        'variant_id' => $variantId,
                        'store_id' => $storeRow['store_id'],
                        'price' => $storeRow['price'],
                        'special_price' => $sp,
                        'cost' => $storeRow['cost'],
                        'stock' => $storeRow['stock'] ?? 0,
                        'sku' => $storeRow['sku'] ?? null,
                    ];
                }
            }
        }

        // Ensure a default variant exists for variant products
        if ($type === ProductTypeEnum::VARIANT() && !collect($variantsJson)->firstWhere('is_default', 'on')) {
            if (isset($variantsJson[0])) {
                $variantsJson[0]['is_default'] = 'on';
            }
        }

        return [$basePayload, $variantsJson, $pricing];
    }

    /**
     * Find or create attribute and value for option pairs during bulk processing.
     */
    private function resolveOrCreateAttributeAndValue(int $sellerId, string $attributeName, string $valueTitle): ?array
    {
        $attrName = trim($attributeName);
        $valTitle = trim($valueTitle);
        if ($attrName === '' || $valTitle === '') {
            return null;
        }

        $attribute = \App\Models\GlobalProductAttribute::where('seller_id', $sellerId)
            ->whereRaw('LOWER(title) = ?', [strtolower($attrName)])
            ->first();
        if (!$attribute) {
            $attribute = \App\Models\GlobalProductAttribute::create([
                'seller_id' => $sellerId,
                'title' => $attrName,
                'label' => $attrName,
            ]);
        }

        $value = \App\Models\GlobalProductAttributeValue::where('global_attribute_id', $attribute->id)
            ->whereRaw('LOWER(title) = ?', [strtolower($valTitle)])
            ->first();
        if (!$value) {
            $value = \App\Models\GlobalProductAttributeValue::create([
                'global_attribute_id' => $attribute->id,
                'title' => $valTitle,
            ]);
        }

        return [
            'attribute_id' => $attribute->id,
            'value_id' => $value->id,
        ];
    }

    public static function getProductWithVariants(int $productId)
    {
        return Product::with([
            'variants.attributes',
            'variants.storeProductVariants',
            'taxClasses',
            'customProductSections.fields',
        ])->find($productId);
    }

    public function updateProduct(Product $product, array $validated, $request): array
    {
        return $this->processProduct($product, $validated, $request, 'update');
    }

    public function storeProduct(array $validated, $request): array
    {
        return $this->processProduct(null, $validated, $request, 'create');
    }

    /**
     * @throws \Exception
     */
    private function processProduct(?Product $product, array $validated, $request, string $mode): array
    {
        DB::beginTransaction();
        try {
            if ($mode === 'create') {
                $product = $this->createProduct($validated);
            } else {
                $this->updateProductDetails($product, $validated);
            }
            $product->taxClasses()->sync($validated['tax_groups'] ?? []);
            $pricingData = json_decode($validated['pricing'], true);
            // Decide based on incoming request, so we can switch type on update as well
            $incomingIsVariant = ($validated['type'] ?? $product->type) === 'variant' && isset($validated['variants_json']);
            $isVariant = $incomingIsVariant;
            if ($isVariant) {
                $this->processVariantProduct($product, $validated, $pricingData, $mode, $request);
            } else {
                // If switching from variant -> simple during update, clean up old variants first
                if ($mode === 'update' && $product->type === 'variant') {
                    $this->cleanupAllVariants($product);
                }
                $this->processSimpleProduct($product, $validated, $pricingData, $mode);
            }

            $this->handleMediaUploads($product, $request);

            // Handle user-friendly custom sections (array-based)
            if ($request->has('custom_sections')) {
                $this->syncCustomSectionsFromForm($product, $request);
            } elseif (!empty($validated['custom_sections_json']) && is_string($validated['custom_sections_json'])) {
                // Fallback to JSON payload if array input not used
                $this->syncCustomSections($product, $validated['custom_sections_json']);
            }

            // If coming from bulk upload and an image URL is provided, attach it as main image
            if ((!$request->hasFile('main_image'))
                && !empty($validated['image_src'])
                && is_string($validated['image_src'])
            ) {
                try {
                    // Ensure we're replacing any existing main image
                    $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
                    SpatieMediaService::uploadFromUrl(
                        model: $product,
                        url: $validated['image_src'],
                        collectionName: SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE()
                    );
                } catch (\Throwable $e) {
                    // Silently ignore URL upload errors during bulk to avoid blocking the whole row
                }
            }
            DB::commit();
            return [
                'success' => true,
                'product' => $product,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync custom product sections and their fields from a JSON payload.
     * Expected JSON structure:
     * [
     *   { id(optional), title, description, sort_order, fields: [ { id, sort_order } ] }
     * ]
     */
    private function syncCustomSections(Product $product, string $sectionsJson): void
    {
        $sections = json_decode($sectionsJson, true);
        if (!is_array($sections)) {
            return; // silently ignore invalid payload (already validated as json)
        }

        // Track IDs we keep to remove others
        $keepSectionIds = [];

        foreach ($sections as $sectionData) {
            if (!is_array($sectionData)) {
                continue;
            }
            $sectionId = $sectionData['id'] ?? null;
            $payload = [
                'title' => $sectionData['title'] ?? '',
                'description' => $sectionData['description'] ?? null,
                'sort_order' => (int)($sectionData['sort_order'] ?? 0),
            ];

            if ($sectionId) {
                $section = CustomProductSection::where('product_id', $product->id)->where('id', $sectionId)->first();
                if ($section) {
                    $section->update($payload);
                } else {
                    $section = CustomProductSection::create(array_merge($payload, [
                        'product_id' => $product->id,
                    ]));
                }
            } else {
                $section = CustomProductSection::create(array_merge($payload, [
                    'product_id' => $product->id,
                ]));
            }

            $keepSectionIds[] = $section->id;

            // Sync fields with sort_order in pivot
            $fieldsArr = $sectionData['fields'] ?? [];
            $syncPayload = [];
            if (is_array($fieldsArr)) {
                foreach ($fieldsArr as $f) {
                    $fid = $f['id'] ?? null;
                    if (!$fid) continue;
                    // ensure the field exists
                    $fieldExists = CustomProductField::where('id', $fid)->exists();
                    if (!$fieldExists) continue;
                    $syncPayload[$fid] = ['sort_order' => (int)($f['sort_order'] ?? 0)];
                }
            }
            // Use sync to replace existing; preserves timestamps on pivot
            $section->fields()->sync($syncPayload);
        }

        // Delete sections not present in payload
        if (!empty($keepSectionIds)) {
            CustomProductSection::where('product_id', $product->id)
                ->whereNotIn('id', $keepSectionIds)
                ->delete();
        } else {
            // If no sections provided, remove all existing
            CustomProductSection::where('product_id', $product->id)->delete();
        }
    }

    /**
     * Sync custom product sections and fields from array-based form input with file uploads.
     * Expected structure in request:
     * custom_sections: [
     *   { id?, title, description?, sort_order?, fields: [ { id?, title?, description?, sort_order?, image? } ] }
     * ]
     */
    private function syncCustomSectionsFromForm(Product $product, $request): void
    {
        $sections = $request->input('custom_sections', []);
        $sectionsFiles = $request->file('custom_sections', []);

        if (!is_array($sections)) {
            return;
        }

        $keepSectionIds = [];

        foreach ($sections as $i => $sectionData) {
            if (!is_array($sectionData)) continue;

            $sectionId = $sectionData['id'] ?? null;
            $payload = [
                'title' => $sectionData['title'] ?? '',
                'description' => $sectionData['description'] ?? null,
                'sort_order' => (int)($sectionData['sort_order'] ?? 0),
            ];

            if ($sectionId) {
                $section = CustomProductSection::where('product_id', $product->id)
                    ->where('id', $sectionId)
                    ->first();
                if ($section) {
                    $section->update($payload);
                } else {
                    $section = CustomProductSection::create(array_merge($payload, [
                        'product_id' => $product->id,
                    ]));
                }
            } else {
                $section = CustomProductSection::create(array_merge($payload, [
                    'product_id' => $product->id,
                ]));
            }

            $keepSectionIds[] = $section->id;

            // Fields handling
            $fieldsArr = $sectionData['fields'] ?? [];
            $fieldsFilesArr = $sectionsFiles[$i]['fields'] ?? [];
            $syncPayload = [];

            if (is_array($fieldsArr)) {
                foreach ($fieldsArr as $j => $fieldData) {
                    if (!is_array($fieldData)) continue;
                    $existingId = $fieldData['id'] ?? null;
                    $title = $fieldData['title'] ?? null;
                    $description = $fieldData['description'] ?? null;
                    $sortOrder = (int)($fieldData['sort_order'] ?? 0);
                    $file = $fieldsFilesArr[$j]['image'] ?? null;

                    if ($existingId) {
                        $field = CustomProductField::find($existingId);
                        if ($field) {
                            // If title/description provided, permit updating them
                            $update = [];
                            if (!is_null($title)) $update['title'] = $title;
                            if (!is_null($description)) $update['description'] = $description;
                            if (!empty($update)) {
                                $field->update($update);
                            }
                            if ($file) {
                                try {
                                    $field->clearMediaCollection('image');
                                    $field->addMedia($file)->toMediaCollection('image');
                                } catch (\Throwable $e) {
                                    // ignore upload error, do not block save
                                }
                            }
                            $syncPayload[$field->id] = ['sort_order' => $sortOrder];
                        }
                    } else {
                        // Create new field
                        $field = CustomProductField::create([
                            'title' => $title ?? '',
                            'description' => $description,
                        ]);
                        if ($file) {
                            try {
                                $field->addMedia($file)->toMediaCollection('image');
                            } catch (\Throwable $e) {
                                // ignore upload error
                            }
                        }
                        $syncPayload[$field->id] = ['sort_order' => $sortOrder];
                    }
                }
            }

            $section->fields()->sync($syncPayload);
        }

        // Cleanup sections not in payload
        if (!empty($keepSectionIds)) {
            CustomProductSection::where('product_id', $product->id)
                ->whereNotIn('id', $keepSectionIds)
                ->delete();
        } else {
            CustomProductSection::where('product_id', $product->id)->delete();
        }
    }

    private function createProduct(array $validated)
    {
        $product = Product::create([
            'seller_id' => $validated['seller_id'],
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'base_prep_time' => $validated['base_prep_time'] ?? 0,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'indicator' => $validated['indicator'] ?? null,
            'image_fit' => $validated['image_fit'] ?? 'cover',
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'quantity_step_size' => $validated['quantity_step_size'] ?? 1,
            'total_allowed_quantity' => $validated['total_allowed_quantity'] ?? null,
            'is_returnable' => (string)($validated['is_returnable'] ?? 0),
            'returnable_days' => $validated['returnable_days'] ?? null,
            'is_cancelable' => (string)($validated['is_cancelable'] ?? 0),
            'cancelable_till' => $validated['cancelable_till'] ?? null,
            'is_attachment_required' => (string)($validated['is_attachment_required'] ?? 0),
            'featured' => (string)($validated['featured'] ?? 0),
            'requires_otp' => (string)($validated['requires_otp'] ?? 0),
            'video_type' => $validated['video_type'],
            'warranty_period' => $validated['warranty_period'] ?? null,
            'guarantee_period' => $validated['guarantee_period'] ?? null,
            'made_in' => $validated['made_in'] ?? null,
            'tags' => json_encode($validated['tags'] ?? []),
            'custom_fields' => $validated['custom_fields'] ?? null,
            'is_inclusive_tax' => (string)($validated['is_inclusive_tax'] ?? 0),
        ]);
        $category = Category::findOrFail($validated['category_id']);
        if ($category->requires_approval) {
            $product->setStatusAttribute(ProductStatusEnum::DRAFT());
            $product->setVerificationStatus(ProductVarificationStatusEnum::PENDING());
        } else {
            $product->setStatusAttribute(ProductStatusEnum::ACTIVE());
            $product->setVerificationStatus(ProductVarificationStatusEnum::APPROVED());
        }
        $product->save();
        event(new ProductStatusAfterUpdate($product));
        return $product;
    }

    private function isVariantProduct(array $validated): bool
    {
        return $validated['type'] === 'variant' && isset($validated['variants_json']);
    }

    private function processVariantProduct($product, array $validated, array $pricingData, string $mode, $request): void
    {
        $variantsData = json_decode($validated['variants_json'], true);
        $newVariantIds = [];
        // Get existing variants if updating
        $existingVariants = ($mode === 'update')
            ? $product->variants()->with('attributes')->get()
            : collect();
        $existingVariantIds = $existingVariants->pluck('id')->toArray();
        foreach ($variantsData as $variantData) {
            $variant = null;
            $imageName = 'variant_image' . $variantData['id'];

            // Try to find matching variant if updating
            if ($mode === 'update' && !empty($variantData['attributes'])) {
                $variant = $this->findMatchingVariant($existingVariants, $variantData, $newVariantIds);
            }
            if ($variant) {
                // Update existing variant
                $variant->update([
                    'title' => !empty($variantData['title']) ? $variantData['title'] : null,
                    'weight' => !empty($variantData['weight']) ? (float)$variantData['weight'] : 1,
                    'height' => !empty($variantData['height']) ? (float)$variantData['height'] : 1,
                    'breadth' => !empty($variantData['breadth']) ? (float)$variantData['breadth'] : 1,
                    'length' => !empty($variantData['length']) ? (float)$variantData['length'] : 1,
                    'availability' => $variantData['availability'] === 'no' ? false : true,
                    'barcode' => !empty($variantData['barcode']) ? $variantData['barcode'] : null,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                ]);


                // Update variant attributes
                if (!empty($variantData['attributes'])) {
                    $variant->attributes()->forceDelete();
                    $this->createVariantAttributes(productId: $product->id, variantId: $variant->id, attributes: $variantData['attributes']);
                }

                $newVariantIds[] = $variant->id;
            } else {
                // Create new variant
                $variant = ProductVariant::create([
                    'uuid' => (string)Str::uuid(),
                    'product_id' => $product->id,
                    'title' => !empty($variantData['title']) ? $variantData['title'] : null,
                    'weight' => !empty($variantData['weight']) ? (float)$variantData['weight'] : 1,
                    'height' => !empty($variantData['height']) ? (float)$variantData['height'] : 1,
                    'breadth' => !empty($variantData['breadth']) ? (float)$variantData['breadth'] : 1,
                    'length' => !empty($variantData['length']) ? (float)$variantData['length'] : 1,
                    'availability' => (!empty($variantData['availability']) && $variantData['availability'] === 'no') ? false : true,
                    'barcode' => !empty($variantData['barcode']) ? $variantData['barcode'] : null,
                    'is_default' => $variantData['is_default'] == 'on' ? true : false,
                ]);


                if (!empty($variantData['attributes'])) {
                    $this->createVariantAttributes(productId: $product->id, variantId: $variant->id, attributes: $variantData['attributes']);
                }

                $newVariantIds[] = $variant->id;
            }

            if ($request->hasFile($imageName)) {
                $this->handleVariantMediaUploads($variant, $imageName);
            }
            // Handle store pricing for this variant
            $this->handleVariantPricing($variant, $variantData, $pricingData, $mode);
        }

        // Delete variants that are no longer in the updated data (only when updating)
        if ($mode === 'update' && !empty($existingVariantIds)) {
            $variantsToDelete = array_diff($existingVariantIds, $newVariantIds);
            if (!empty($variantsToDelete)) {
                ProductVariant::whereIn('id', $variantsToDelete)->delete();
            }
        }
    }

    private function findMatchingVariant($existingVariants, $variantData, $alreadyMatchedIds)
    {
        // Create a map of attribute_id => value_id for easier comparison
        $variantAttributeMap = [];
        foreach ($variantData['attributes'] as $attr) {
            $variantAttributeMap[$attr['attribute_id']] = $attr['value_id'];
        }

        // Check each existing variant for a match
        foreach ($existingVariants as $existingVariant) {
            // Skip if this variant has already been matched
            if (in_array($existingVariant->id, $alreadyMatchedIds)) {
                continue;
            }

            // Get existing variant attributes
            $existingAttributes = $existingVariant->attributes;

            // If attribute count doesn't match, it's not the same variant
            if (count($existingAttributes) !== count($variantAttributeMap)) {
                continue;
            }

            // Check if all attributes match
            $allMatch = true;
            foreach ($existingAttributes as $attr) {
                if (!isset($variantAttributeMap[$attr->global_attribute_id]) ||
                    $variantAttributeMap[$attr->global_attribute_id] != $attr->global_attribute_value_id) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return $existingVariant;
            }
        }

        return null;
    }

    private function handleVariantPricing($variant, $variantData, $pricingData, $mode): void
    {
        if (empty($pricingData['variant_pricing'])) {
            return;
        }

        // Delete existing store pricing if updating
        if ($mode === 'update') {
            StoreProductVariant::where('product_variant_id', $variant->id)->forceDelete();
        }

        // Find pricing data for this variant
        $variantPricing = array_filter(
            $pricingData['variant_pricing'],
            fn($vp) => isset($vp['variant_id']) && $vp['variant_id'] === $variantData['id']
        );

        // Create new store pricing
        if (!empty($variantPricing)) {
            $this->createStoreProductVariants($variant->id, $variantPricing);
        }
    }

    private function processSimpleProduct($product, $validated, array $pricingData, string $mode): void
    {
        $variant = null;
        if ($mode === 'update') {
            // Get the existing variant or create a new one if it doesn't exist
            $variant = $product->variants()->first();
        }
        $variantData = [
            'uuid' => (string)Str::uuid(),
            'product_id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'weight' => !empty($validated['weight']) ? $validated['weight'] : '1',
            'height' => !empty($validated['height']) ? $validated['height'] : '1',
            'breadth' => !empty($validated['breadth']) ? $validated['breadth'] : '1',
            'length' => !empty($validated['length']) ? $validated['length'] : '1',
            'barcode' => !empty($validated['barcode']) ? $validated['barcode'] : null,
            'availability' => 1,
            'is_default' => true,
        ];

        if ($variant) {
            $variant->update($variantData);
        } else {
            $variant = ProductVariant::create($variantData);
        }
        if (!empty($pricingData['store_pricing'])) {
            // Delete existing store pricing if updating
            if ($mode === 'update') {
                StoreProductVariant::where('product_variant_id', $variant->id)->forceDelete();
            }

            // Create new store pricing
            $this->createStoreProductVariants($variant->id, $pricingData['store_pricing']);
        }
    }

    private function createVariantAttributes(int $productId, int $variantId, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            ProductVariantAttribute::create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'global_attribute_id' => $attribute['attribute_id'],
                'global_attribute_value_id' => $attribute['value_id'],
            ]);
        }
    }

    private function createStoreProductVariants(int $variantId, array $storePricings): void
    {
        foreach ($storePricings as $pricing) {
            StoreProductVariant::create([
                'product_variant_id' => $variantId,
                'store_id' => $pricing['store_id'],
                'price' => $pricing['price'] ?? null,
                'sku' => $pricing['sku'],
                'special_price' => !empty($pricing['special_price']) ? $pricing['special_price'] : $pricing['price'],
                'cost' => $pricing['cost'] ?? null,
                'stock' => $pricing['stock'] ?? 0,
            ]);
        }
    }

    private function updateProductDetails(Product $product, array $validated): void
    {
        $product->update([
            // Allow type to be updated so switching simple <-> variant is possible
            'type' => $validated['type'] ?? $product->type,
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'base_prep_time' => $validated['base_prep_time'] ?? 0,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'indicator' => $validated['indicator'] ?? null,
            'image_fit' => $validated['image_fit'] ?? $product->image_fit,
            'hsn_code' => $validated['hsn_code'] ?? null,
            'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
            'quantity_step_size' => $validated['quantity_step_size'] ?? 1,
            'total_allowed_quantity' => $validated['total_allowed_quantity'] ?? null,
            'is_returnable' => (string)($validated['is_returnable'] ?? 0),
            'returnable_days' => $validated['returnable_days'] ?? null,
            'is_cancelable' => (string)($validated['is_cancelable'] ?? 0),
            'cancelable_till' => $validated['cancelable_till'] ?? null,
            'is_attachment_required' => (string)($validated['is_attachment_required'] ?? 0),
            'featured' => (string)($validated['featured'] ?? 0),
            'requires_otp' => (string)($validated['requires_otp'] ?? 0),
            'video_type' => $validated['video_type'],
            'warranty_period' => $validated['warranty_period'] ?? null,
            'guarantee_period' => $validated['guarantee_period'] ?? null,
            'made_in' => $validated['made_in'] ?? null,
            'tags' => json_encode($validated['tags'] ?? []),
            'custom_fields' => $validated['custom_fields'] ?? null,
            'is_inclusive_tax' => (string)($validated['is_inclusive_tax'] ?? 0),
        ]);
        $category = Category::findOrFail($validated['category_id']);
        if ($category->requires_approval) {
            $product->setStatusAttribute(ProductStatusEnum::DRAFT());
            $product->setVerificationStatus(ProductVarificationStatusEnum::PENDING());
        } else {
            $product->setStatusAttribute(ProductStatusEnum::ACTIVE());
            $product->setVerificationStatus(ProductVarificationStatusEnum::APPROVED());
        }
        $product->save();
        event(new ProductStatusAfterUpdate($product));

    }

    /**
     * Clean up all variants and their related records for a product.
     * Used when switching from variant product to simple product during update.
     */
    private function cleanupAllVariants(Product $product): void
    {
        $variantIds = $product->variants()->pluck('id')->toArray();
        if (empty($variantIds)) {
            return;
        }
        // Delete related store product variants and attributes first
        StoreProductVariant::whereIn('product_variant_id', $variantIds)->forceDelete();
        ProductVariantAttribute::whereIn('product_variant_id', $variantIds)->forceDelete();
        // Now delete the variants themselves (force delete to clean media as well)
        ProductVariant::whereIn('id', $variantIds)->forceDelete();
    }

    private function handleVariantMediaUploads($variant, $payload_image): void
    {
        // Remove the existing main image
        $variant->clearMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
        // Upload the new image
        $variant->addMediaFromRequest($payload_image)->toMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
    }

    private function handleMediaUploads($product, $request): void
    {
        if ($request->hasFile('main_image')) {
            // Remove existing main image
            $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
            // Upload new main image
            SpatieMediaService::upload(model: $product, media: SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
        }

        if ($request->hasFile('additional_images')) {
            // Remove existing additional images if requested
            $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());

            // Upload new additional images
            foreach ($request->file('additional_images') as $image) {
                SpatieMediaService::uploadFromRequest($product, $image, SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());
            }
        }

        if (ProductVideoTypeEnum::LOCAL() === $request->video_type) {
            if ($request->hasFile('product_video')) {
                // Remove existing video
                $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_VIDEO());
                // Upload new video
                SpatieMediaService::upload(model: $product, media: SpatieMediaCollectionName::PRODUCT_VIDEO());
            }
        } else {
            $product->update(['video_link' => $request['video_link']]);
        }
    }

    // ===== Helpers for bulk ZIP images (attach from local file paths) =====
    private function isValidLocalImage(string $path): bool
    {
        if (!is_file($path)) return false;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) return false;
        $size = @filesize($path);
        if ($size === false) return false;
        return $size <= 2 * 1024 * 1024; // 2 MB
    }

    public function setMainImageFromPath(Product $product, string $path): void
    {
        if (!$this->isValidLocalImage($path)) {
            $imageName = basename($path);
            throw new \InvalidArgumentException("Main image '{$imageName}' must be jpg, jpeg, png or webp and not exceed 2MB.");
        }
        // Replace existing main image
        $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
        // Preserve the original source file so it can be reused for other products with the same name
        $product->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection(SpatieMediaCollectionName::PRODUCT_MAIN_IMAGE());
    }

    public function setAdditionalImagesFromPaths(Product $product, array $paths): void
    {
        // Clear existing additional images before adding
        $product->clearMediaCollection(SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());
        if (empty($paths)) return;
        foreach ($paths as $p) {
            if (!$this->isValidLocalImage($p)) continue; // skip invalid files silently
            // Preserve original so the same temp file can be used multiple times if needed
            $product->addMedia($p)
                ->preservingOriginal()
                ->toMediaCollection(SpatieMediaCollectionName::PRODUCT_ADDITIONAL_IMAGE());
        }
    }

    public function setVariantImageFromPath(ProductVariant $variant, string $path): void
    {
        if (!$this->isValidLocalImage($path)) {
            $imageName = basename($path);
            throw new \InvalidArgumentException("Variant image '{$imageName}' must be jpg, jpeg, png or webp and not exceed 2MB.");
        }
        $variant->clearMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
        // Preserve original so other variants/products can still access the same file path if necessary
        $variant->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection(SpatieMediaCollectionName::VARIANT_IMAGE());
    }
}
