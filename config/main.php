<?php

return [
    'auth' => [
        'socialite' => false
    ],
    'active_day_check' => [
        'warehouse' => env('WAREHOUSE_ACTIVE', false),
        'crm' => env('CRM_ACTIVE', false),
    ],
    'warehouse_users' => [
        6 => 15,
        11 => 33
    ],
    'crm_users' => [
        2 => 10,
        8 => 10,
    ]
];
