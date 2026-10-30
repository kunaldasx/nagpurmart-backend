<?php

use App\Models\StoreProductVariant;

it('adds tax to a price when tax is exclusive', function () {
    $details = StoreProductVariant::calculatePriceTaxDetails(100, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], false);

    expect($details['taxable_amount'])->toBe(100.0)
        ->and($details['tax_amount'])->toBe(5.0)
        ->and($details['price_with_tax'])->toBe(105.0)
        ->and($details['tax_breakdown'][0]['amount'])->toBe(5.0);
});

it('extracts tax from an inclusive price', function () {
    $details = StoreProductVariant::calculatePriceTaxDetails(105, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], true);

    expect($details['taxable_amount'])->toBe(100.0)
        ->and($details['tax_amount'])->toBe(5.0)
        ->and($details['price_with_tax'])->toBe(105.0)
        ->and($details['tax_breakdown'][0]['amount'])->toBe(5.0);
});

it('keeps low-value inclusive lines equal to their entered gross price after rounding', function () {
    $details = StoreProductVariant::calculateLineTaxDetails(0.10, 1, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], true);

    expect($details['taxable_amount'])->toBe(0.10)
        ->and($details['tax_amount'])->toBe(0.0)
        ->and($details['price_with_tax'])->toBe(0.10)
        ->and($details['taxable_amount'] + $details['tax_amount'])->toBe($details['price_with_tax']);
});

it('splits multiple rates in a group and preserves their names', function () {
    $details = StoreProductVariant::calculatePriceTaxDetails(100, [
        ['tax_group' => 'GST', 'name' => 'CGST', 'rate' => 2.5],
        ['tax_group' => 'GST', 'name' => 'SGST', 'rate' => 2.5],
    ], false);

    expect($details['tax_percent'])->toBe(5.0)
        ->and($details['tax_amount'])->toBe(5.0)
        ->and($details['tax_breakdown'])->toHaveCount(2)
        ->and($details['tax_breakdown'][0]['amount'])->toBe(2.5)
        ->and($details['tax_breakdown'][1]['amount'])->toBe(2.5);
});

it('rounds tax on the order line rather than rounding each unit independently', function () {
    $details = StoreProductVariant::calculateLineTaxDetails(0.05, 3, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], false);

    expect($details['taxable_amount'])->toBe(0.15)
        ->and($details['tax_amount'])->toBe(0.01)
        ->and($details['price_with_tax'])->toBe(0.16)
        ->and($details['tax_breakdown'][0]['amount'])->toBe(0.01);
});

it('allocates rounded tax cents without component totals exceeding line tax', function () {
    $details = StoreProductVariant::calculateLineTaxDetails(0.10, 1, [
        ['tax_group' => 'GST', 'name' => 'Component A', 'rate' => 5],
        ['tax_group' => 'GST', 'name' => 'Component B', 'rate' => 5],
    ], false);

    expect($details['tax_amount'])->toBe(0.01)
        ->and(array_sum(array_column($details['tax_breakdown'], 'amount')))->toBe(0.01)
        ->and($details['tax_breakdown'][0]['amount'])->toBe(0.01)
        ->and($details['tax_breakdown'][1]['amount'])->toBe(0.0);
});

it('applies line tax correctly for both inclusive and exclusive prices', function () {
    $exclusive = StoreProductVariant::calculateLineTaxDetails(100, 2, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], false);
    $inclusive = StoreProductVariant::calculateLineTaxDetails(105, 2, [
        ['tax_group' => 'GST', 'name' => 'GST 5%', 'rate' => 5],
    ], true);

    expect($exclusive['taxable_amount'])->toBe(200.0)
        ->and($exclusive['tax_amount'])->toBe(10.0)
        ->and($exclusive['price_with_tax'])->toBe(210.0)
        ->and($inclusive['taxable_amount'])->toBe(200.0)
        ->and($inclusive['tax_amount'])->toBe(10.0)
        ->and($inclusive['price_with_tax'])->toBe(210.0);
});

it('accepts a GSTIN in the expected format and rejects invalid shapes', function () {
    $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';

    expect(preg_match($pattern, '27AAPFU0939F1ZV'))->toBe(1)
        ->and(preg_match($pattern, 'not-a-gstin'))->toBe(0);
});
