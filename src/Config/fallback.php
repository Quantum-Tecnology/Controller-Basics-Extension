<?php

declare(strict_types = 1);

return [
    'on_boot'             => env('FALLBACK_ON_BOOT', true),
    'auto_fallback_redis' => env('AUTO_FALLBACK_REDIS', false),
];
