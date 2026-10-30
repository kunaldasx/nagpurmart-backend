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
