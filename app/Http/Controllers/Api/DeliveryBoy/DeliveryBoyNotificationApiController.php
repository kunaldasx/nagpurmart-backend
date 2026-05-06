<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Http\Controllers\Api\NotificationApiController;
use Dedoc\Scramble\Attributes\Group;

#[Group('Notifications')]
class DeliveryBoyNotificationApiController extends NotificationApiController
{
    // Inherits all notification actions from the base controller
}
