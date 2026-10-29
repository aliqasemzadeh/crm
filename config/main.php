<?php

return [
    'auth' => [
        'socialite' => false,
    ],
    'active_day_check' => [
        'warehouse' => env('WAREHOUSE_ACTIVE', false),
        'crm' => env('CRM_ACTIVE', false),
    ],
    'defaults' => [
        'crm_follow_up_quota' => (int) env('CRM_FOLLOW_UP_QUOTA', 10),
        'warehouse_check_quota' => (int) env('WAREHOUSE_CHECK_QUOTA', 15),
    ],
    'price_notification' => env('PRICE_NOTIFICATION', false),
    'hr_attendance_alert' => env('HR_ATTENDANCE_ALERT', true),
];
