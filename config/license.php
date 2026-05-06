<?php

return [
    // License validation endpoint
    'endpoint' => env('LICENSE_ENDPOINT', 'https://validator.infinitietech.com/home/validator'),

    // How often to revalidate the license with the remote server (in minutes)
    'recheck_minutes' => (int) env('LICENSE_RECHECK_MINUTES', 720), // default 12 hours
];
