<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Api\NotificationApiController;
use Dedoc\Scramble\Attributes\Group;

#[Group('Seller Notifications')]
class SellerNotificationApiController extends NotificationApiController
{
    // Inherits all notification actions from the base controller
}
