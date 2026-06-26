<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MikroTik Default Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for connecting to MikroTik routers via REST API.
    |
    */

    'default_port' => env('MIKROTIK_DEFAULT_PORT', 8728),

    'timeout' => env('MIKROTIK_TIMEOUT', 10),

    'retry_times' => env('MIKROTIK_RETRY_TIMES', 3),

    'retry_sleep' => env('MIKROTIK_RETRY_SLEEP', 100),
];
