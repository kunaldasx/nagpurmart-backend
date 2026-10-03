<?php

use App\Enums\SellerPermissionEnum;

test('seller permissions include the bag module', function () {
    $permissions = SellerPermissionEnum::groupedPermissions();

    expect($permissions)->toHaveKey('bag');
    expect($permissions['bag']['name'])->toBe('Bag');
    expect($permissions['bag']['permissions'])->toContain(
        SellerPermissionEnum::BAG_VIEW(),
        SellerPermissionEnum::BAG_CREATE(),
        SellerPermissionEnum::BAG_EDIT(),
        SellerPermissionEnum::BAG_DELETE(),
    );
});

test('seller order popup permission is available in the order group', function () {
    $permissions = SellerPermissionEnum::groupedPermissions();

    expect($permissions)->toHaveKey('order');
    expect($permissions['order']['permissions'])->toContain(SellerPermissionEnum::ORDER_POPUP());
});
