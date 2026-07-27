<?php

return [
    'token' => env('SMS_TOKEN'),
    'gateway' => env('SMS_GATEWAY'),
    'endpoint' => env('SMS_ENDPOINT', 'https://srscrm.ir/api/sms/send'),
];
