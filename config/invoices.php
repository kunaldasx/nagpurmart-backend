<?php

return [
    'chromium_path' => env('INVOICE_CHROMIUM_PATH', 'chromium'),
    'timeout' => (int) env('INVOICE_PDF_TIMEOUT', 120),
];
