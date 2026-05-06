<?php

namespace App\Http\Requests\Product;

use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\Product\ProductImageFitEnum;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUpdateProductRequest extends FormRequest
{
    protected ?array $sellerStoreIds = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tax_groups' => 'nullable|array',
            'type' => ['required', new Enum(ProductTypeEnum::class)],
            'base_prep_time' => 'required|integer|min:0',
            'short_description' => 'required|string|max:255',
            'description' => 'required|string',
            'indicator' => 'nullable|string',
            'image_fit' => ['required', new Enum(ProductImageFitEnum::class)],
            'minimum_order_quantity' => 'required|integer|min:1',
            'quantity_step_size' => 'required|integer|min:1',
            'total_allowed_quantity' => 'required|integer|min:0',
            'is_returnable' => 'nullable|boolean',
            'returnable_days' => 'required_if:is_returnable,1|nullable|integer|min:0',
            'is_cancelable' => 'nullable|boolean',
            'cancelable_till' => 'required_if:is_cancelable,1|nullable|in:'. implode(',', [OrderItemStatusEnum::PENDING(), OrderItemStatusEnum::AWAITING_STORE_RESPONSE(), OrderItemStatusEnum::ACCEPTED(), OrderItemStatusEnum::PREPARING()]),
            'is_attachment_required' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'requires_otp' => 'nullable|boolean',
            'video_type' => 'nullable|string',
            'video_link' => 'nullable|string|url',
            'warranty_period' => 'nullable|string',
            'guarantee_period' => 'nullable|string',
            'made_in' => 'nullable|string',
            'hsn_code' => 'nullable|string',
            // Image validations: limit size (2 MB) and restrict to common extensions
            'main_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
            'additional_images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'product_video' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            // Dynamic custom fields
            'custom_fields' => 'nullable|array',
            'custom_fields.*' => 'nullable|string|max:255',
            // User-friendly Custom Sections (array-based)
            'custom_sections' => 'nullable|array',
            'custom_sections.*.id' => 'nullable|integer|exists:custom_product_sections,id',
            'custom_sections.*.title' => 'required_with:custom_sections|string|max:255',
            'custom_sections.*.description' => 'nullable|string',
            'custom_sections.*.sort_order' => 'nullable|integer|min:0',
            'custom_sections.*.fields' => 'nullable|array',
            'custom_sections.*.fields.*.id' => 'nullable|integer|exists:custom_product_fields,id',
            'custom_sections.*.fields.*.title' => 'required_without:custom_sections.*.fields.*.id|string|max:255',
            'custom_sections.*.fields.*.description' => 'nullable|string',
            'custom_sections.*.fields.*.sort_order' => 'nullable|integer|min:0',
            'custom_sections.*.fields.*.image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            // Inclusive tax flag
            'is_inclusive_tax' => 'nullable|boolean',
            'pricing' => 'required|json',
            'variants_json' => 'required_if:type,variant|json',
            // Custom product sections as JSON payload similar to pricing/variants
            // Structure: [{id(optional), title, description, sort_order, fields:[{id, sort_order}]}]
            'custom_sections_json' => 'nullable|json',
            'weight' => 'nullable|min:0',
            'height' => 'nullable|min:0',
            'length' => 'nullable|min:0',
            'breadth' => 'nullable|min:0',
            'barcode' => 'required_if:type,simple|nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Product Title',
            'category_id' => 'Category',
            'brand_id' => 'Brand',
            'type' => 'Product Type',
            'image_fit' => 'Image Fit',
            'short_description' => 'Short Description',
            'description' => 'Description',
            'main_image' => 'Main Image',
            'additional_images.*' => 'Additional Image',
            'pricing' => 'Pricing Information',
            'variants_json' => 'Variants Information',
            'custom_sections_json' => 'Custom Sections',
            'custom_sections' => 'Custom Sections',
            'custom_sections.*.title' => 'Section Title',
            'custom_sections.*.description' => 'Section Description',
            'custom_sections.*.sort_order' => 'Section Sort Order',
            'custom_sections.*.fields.*.title' => 'Field Title',
            'custom_sections.*.fields.*.description' => 'Field Description',
            'custom_sections.*.fields.*.sort_order' => 'Field Sort Order',
            'custom_sections.*.fields.*.image' => 'Field Image',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('labels.title_required'),
            'category_id.required' => __('labels.category_id_required'),
            'type.required' => __('labels.type_required'),
            'main_image.required' => __('labels.main_image_required'),
            'pricing.required' => __('labels.pricing_required'),
            'pricing.json' => __('labels.pricing_json'),
            'variants_json.required_if' => __('labels.variants_json_required_if'),
            'custom_sections_json.json' => __('labels.invalid_json'),
            'returnable_days.required_if' => __('labels.returnable_days_required_if'),
            'cancelable_till.required_if' => __('labels.cancelable_till_required_if'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateQuantityLogic($validator);

            $pricingArr = $this->validatePricingJsonStructure($validator);
            if (!$pricingArr) {
                return;
            }

            $this->validateSellerStorePricing($validator, $pricingArr);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->input('type');
            if ($type == ProductTypeEnum::VARIANT() || $type == ProductTypeEnum::VARIANT) {
                $this->validateVariantProduct($validator, $pricingArr);
            } else {
                $this->validateSimpleProduct($validator, $pricingArr);
            }
        });
    }

    /**
     * Validate quantity-related fields.
     *
     * @param $validator
     * @return void
     */
    protected function validateQuantityLogic($validator): void
    {
        $minQty = $this->input('minimum_order_quantity');
        $stepQty = $this->input('quantity_step_size');
        $totalQty = $this->input('total_allowed_quantity');

        if ($minQty !== null && $stepQty !== null && $stepQty < $minQty) {
            $validator->errors()->add('quantity_step_size', __('messages.quantity_step_size_gte_minimum_order_quantity'));
        }

        if ($totalQty !== null && $stepQty !== null && $stepQty > $totalQty) {
            $validator->errors()->add('quantity_step_size', __('messages.quantity_step_size_lte_total_allowed_quantity'));
        }

        if ($minQty !== null && $totalQty !== null && $minQty > $totalQty) {
            $validator->errors()->add('minimum_order_quantity', __('messages.minimum_order_quantity_lte_total_allowed_quantity'));
        }
    }

    /**
     * Validate pricing JSON structure.
     *
     * @param $validator
     * @return array|null
     */
    protected function validatePricingJsonStructure($validator): ?array
    {
        $pricingJson = $this->input('pricing');
        $pricingArr = json_decode($pricingJson, true);

        if (!is_array($pricingArr)) {
            $validator->errors()->add('pricing', __('labels.pricing_json_object'));
            return null;
        }

        return $pricingArr;
    }

    /**
     * Ensure submitted pricing contains at least one store owned by the seller.
     *
     * @param $validator
     * @param array $pricingArr
     * @return void
     */
    protected function validateSellerStorePricing($validator, array $pricingArr): void
    {
        if (!$this->isSellerRequest()) {
            return;
        }

        $sellerStoreIds = $this->getSellerStoreIds();
        if (empty($sellerStoreIds)) {
            $validator->errors()->add('pricing', __('labels.seller_store_pricing_required_for_product'));
            return;
        }

        $pricingRows = [];
        if (isset($pricingArr['store_pricing']) && is_array($pricingArr['store_pricing'])) {
            $pricingRows = array_merge($pricingRows, $pricingArr['store_pricing']);
        }

        if (isset($pricingArr['variant_pricing']) && is_array($pricingArr['variant_pricing'])) {
            $pricingRows = array_merge($pricingRows, $pricingArr['variant_pricing']);
        }

        $hasSellerStorePricing = collect($pricingRows)
            ->contains(fn ($row) => isset($row['store_id']) && is_numeric($row['store_id']) && in_array((int)$row['store_id'], $sellerStoreIds, true));

        if (!$hasSellerStorePricing) {
            $validator->errors()->add('pricing', __('labels.seller_store_pricing_required_for_product'));
        }
    }

    /**
     * Validate variant product pricing and details.
     *
     * @param $validator
     * @param array $pricingArr
     * @return void
     */
    protected function validateVariantProduct($validator, array $pricingArr): void
    {
        if (!isset($pricingArr['variant_pricing']) || !is_array($pricingArr['variant_pricing'])) {
            $validator->errors()->add('pricing', __('labels.variant_pricing_array'));
            return;
        }

        $variantPricingArr = $pricingArr['variant_pricing'];
        $variants = json_decode($this->input('variants_json'), true) ?? [];
        $variantIds = array_column($variantPricingArr, 'variant_id') ?? [];
        $isDefaultSet = false;

        // Validate barcode uniqueness
        $this->validateVariantBarcodeUniqueness($validator, $variants);
        if (empty($variants)) {
            $validator->errors()->add('variants_json', __('labels.variant_required'));
        }

        foreach ($variants as $variant) {
            if ($this->validateVariantDetails($validator, $variant, $variantIds)) {
                return;
            }
            if ($variant['is_default'] == "on") {
                $isDefaultSet = true;
            }
        }

        foreach ($variantPricingArr as $index => $row) {
            $this->validatePricingRow($validator, $row, "variant_pricing.$index");
        }

        if (!$isDefaultSet) {
            $validator->errors()->add('variants_json', __('labels.default_variant_required'));
        }
    }

    /**
     * Validate individual variant details.
     *
     * @param $validator
     * @param array $variant
     * @param array $variantIds
     * @return bool Returns true if validation should stop
     */
    protected function validateVariantDetails($validator, array $variant, array $variantIds): bool
    {
        if(!empty($this->file('variant_image'. $variant['id']))) {
            $variantImage = $this->file('variant_image' . $variant['id']);
            if (!in_array(strtolower($variantImage->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                $validator->errors()->add('variants_json', $variant['title'] . ' variant image must be a file of type: jpg, jpeg, png, webp');
                return true;
            }
            if ($variantImage->getSize() > 2 * 1024 * 1024) { // 2MB in bytes
                $validator->errors()->add('variants_json', $variant['title'] . ' variant image may not be greater than 2MB');
                return true;
            }
        }
        if (empty($variant['attributes'])) {
            $validator->errors()->add('variants_json', __('labels.attributes_required'));
            return true;
        }

        if (empty($variant['title'])) {
            $validator->errors()->add('variants_json', __('labels.variant_title_required'));
            return true;
        }

        if (empty($variant['barcode'])) {
            $validator->errors()->add('variants_json', $variant['title'] . ' ' . __('labels.variant_barcode_required'));
            return true;
        }
        if (empty($variant['weight'])) {
            $validator->errors()->add('variants_json', $variant['title'] . ' Variant weight is required.');
            return true;
        }
        if (empty($variant['height'])) {
            $validator->errors()->add('variants_json', $variant['title'] . ' Variant height is required.');
            return true;
        }
        if (empty($variant['length'])) {
            $validator->errors()->add('variants_json', $variant['title'] . ' Variant length is required.');
            return true;
        }
        if (empty($variant['breadth'])) {
            $validator->errors()->add('variants_json', $variant['title'] . ' Variant breadth is required.');
            return true;
        }

        if (!in_array($variant['id'], $variantIds)) {
            $validator->errors()->add(
                'pricing',
                __('Pricing information is missing for variant ID: ' . $variant['title'])
            );
        }

        return false;
    }

    /**
     * Validate that variant barcodes are unique.
     *
     * @param $validator
     * @param array $variants
     * @return void
     */
    protected function validateVariantBarcodeUniqueness($validator, array $variants): void
    {
        $barcodes = [];
        $duplicateBarcodes = [];

        foreach ($variants as $variant) {
            if (!empty($variant['barcode'])) {
                $barcode = $variant['barcode'];
                if (in_array($barcode, $barcodes)) {
                    if (!in_array($barcode, $duplicateBarcodes)) {
                        $duplicateBarcodes[] = $barcode;
                    }
                } else {
                    $barcodes[] = $barcode;
                }
            }
        }

        if (!empty($duplicateBarcodes)) {
            $validator->errors()->add(
                'variants_json',
                'Variant barcodes must be unique. Duplicate barcodes found: ' . implode(', ', $duplicateBarcodes)
            );
        }
    }

    /**
     * Validate simple product pricing.
     *
     * @param $validator
     * @param array $pricingArr
     * @return void
     */
    protected function validateSimpleProduct($validator, array $pricingArr): void
    {
        if (!isset($pricingArr['store_pricing']) || !is_array($pricingArr['store_pricing'])) {
            $validator->errors()->add('pricing', __('labels.store_pricing_array'));
            return;
        }

        foreach ($pricingArr['store_pricing'] as $index => $row) {
            $this->validatePricingRow($validator, $row, "store_pricing.$index");
        }
    }

    /**
     * Validate a single pricing row.
     *
     * @param $validator
     * @param array $row
     * @param string $prefix
     * @return void
     */
    protected function validatePricingRow($validator, array $row, string $prefix): void
    {
        $this->validatePricingFields($validator, $row, $prefix);
        $this->validatePricingStoreOwnership($validator, $row, $prefix);
        $this->validateSpecialPriceLogic($validator, $row, $prefix);
    }

    /**
     * Validate individual pricing fields.
     *
     * @param $validator
     * @param array $row
     * @param string $prefix
     * @return void
     */
    protected function validatePricingFields($validator, array $row, string $prefix): void
    {
        $fieldValidations = [
            'store_id' => [
                'condition' => !isset($row['store_id']) || !is_numeric($row['store_id']),
                'message' => __('validation.store_id_required'),
            ],
            'price' => [
                'condition' => !isset($row['price']) || !is_numeric($row['price']),
                'message' => __('labels.price_required_numeric'),
            ],
            'special_price' => [
                'condition' => isset($row['special_price']) && $row['special_price'] !== '' &&
                    (!is_numeric($row['special_price']) || $row['special_price'] < 0),
                'message' => __('labels.special_price_numeric_non_negative'),
            ],
            'cost' => [
                'condition' => !isset($row['cost']) || !is_numeric($row['cost']),
                'message' => __('labels.cost_required_numeric'),
            ],
            'stock' => [
                'condition' => !isset($row['stock']) || !is_numeric($row['stock']) || intval($row['stock']) < 0,
                'message' => __('labels.stock_required_non_negative'),
            ],
            'sku' => [
                'condition' => !isset($row['sku']) || !$row['sku'],
                'message' => __('labels.sku_required'),
            ],
        ];


        foreach ($fieldValidations as $field => $validation) {
            if ($validation['condition']) {
                $validator->errors()->add("pricing.$prefix.$field", $validation['message']);
            }
        }
    }

    /**
     * Ensure each pricing row points to a store owned by the authenticated seller.
     *
     * @param $validator
     * @param array $row
     * @param string $prefix
     * @return void
     */
    protected function validatePricingStoreOwnership($validator, array $row, string $prefix): void
    {
        if (!$this->isSellerRequest()) {
            return;
        }

        if (!isset($row['store_id']) || !is_numeric($row['store_id'])) {
            return;
        }

        if (!in_array((int)$row['store_id'], $this->getSellerStoreIds(), true)) {
            $validator->errors()->add("pricing.$prefix.store_id", __('labels.invalid_store_for_seller'));
        }
    }

    /**
     * Validate special price logic in relation to regular price.
     *
     * @param $validator
     * @param array $row
     * @param string $prefix
     * @return void
     */
    protected function validateSpecialPriceLogic($validator, array $row, string $prefix): void
    {
        $hasValidPrice = isset($row['price']) && is_numeric($row['price']);
        $hasValidSpecialPrice = isset($row['special_price']) && is_numeric($row['special_price']) && $row['special_price'] !== '';

        if ($hasValidPrice && $hasValidSpecialPrice && $row['special_price'] >= $row['price']) {
            $validator->errors()->add(
                "pricing.$prefix.special_price",
                __('labels.special_price_less_than_price')
            );
        }

    }

    /**
     * Determine whether the current request is coming from an authenticated seller.
     *
     * @return bool
     */
    protected function isSellerRequest(): bool
    {
        return $this->user()?->seller() !== null;
    }

    /**
     * Get authenticated seller store IDs once per request.
     *
     * @return array
     */
    protected function getSellerStoreIds(): array
    {
        if ($this->sellerStoreIds !== null) {
            return $this->sellerStoreIds;
        }

        $seller = $this->user()?->seller();
        if (!$seller) {
            return $this->sellerStoreIds = [];
        }

        return $this->sellerStoreIds = Store::query()
            ->where('seller_id', $seller->id)
            ->pluck('id')
            ->map(fn ($id) => (int)$id)
            ->all();
    }
}
