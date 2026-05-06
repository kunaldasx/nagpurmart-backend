<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\NotificationApiController;
use Dedoc\Scramble\Attributes\Group;

#[Group('Notifications')]
class UserNotificationApiController extends NotificationApiController
{
    // Inherits all notification actions from the base controller
}
