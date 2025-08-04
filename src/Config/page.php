<?php

declare(strict_types = 1);

return [
    'per_page' => env('CONTROLLER_GRAPHQL_PER_PAGE', 10),
    'max_page' => env('CONTROLLER_GRAPHQL_MAX_PAGE', 50),
];
