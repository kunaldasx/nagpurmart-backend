<?php

namespace App\Services;

use App\Enums\Attribute\AttributeTypesEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class AttributeService
{
    /**
     * Create a new attribute for the authenticated seller.
     *
     * @param array $data
     * @param int $sellerId
     * @return GlobalProductAttribute
     */
    public function createAttribute(array $data, int $sellerId): GlobalProductAttribute
    {
        $data['seller_id'] = $sellerId;
        return GlobalProductAttribute::create($data);
    }

    /**
     * Create multiple attribute values for a given attribute id.
     * Handles swatche types (text/color/image) consistent with web logic.
     *
     * @param int $attributeId
     * @param array $titles Array of value titles
     * @param array|null $swatcheValues For text/color, array of values; for image, array of UploadedFile or null
     * @return array<GlobalProductAttributeValue>
     */
    public function createAttributeValues(int $attributeId, array $titles, ?array $swatcheValues = null): array
    {
        $created = [];

        DB::beginTransaction();
        try {
            /** @var GlobalProductAttribute $attribute */
            $attribute = GlobalProductAttribute::findOrFail($attributeId);
            $swatcheType = $attribute->swatche_type;

            foreach ($titles as $index => $title) {
                $value = new GlobalProductAttributeValue();
                $value->global_attribute_id = $attributeId;
                $value->title = $title;

                if ($swatcheType === AttributeTypesEnum::IMAGE->value) {
                    // Save first to attach media later
                    $value->swatche_value = null;
                    $value->save();

                    // If an image is provided for this index, attach it
                    $file = $swatcheValues[$index] ?? null;
                    if ($file instanceof UploadedFile) {
                        $value->addMedia($file)->toMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    }
                } else {
                    // Text or Color
                    $value->swatche_value = $swatcheValues[$index] ?? null;
                    $value->save();
                }

                $created[] = $value;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $created;
    }
}
