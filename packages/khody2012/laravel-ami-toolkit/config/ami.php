<?php

return [
    'host' => env('AMI_HOST', '127.0.0.1'),
    'port' => env('AMI_PORT', 5038),
    'username' => env('AMI_USERNAME', 'Hello'),
    'secret' => env('AMI_SECRET', 'Hello'),
    'timeout' => env('AMI_TIMEOUT', 30),
    'broadcast' => env('AMI_BROADCAST', true),
    'log_unknown_events' => env('AMI_LOG_UNKNOWN_EVENTS', false),
    'heartbeat_interval' => env('AMI_HEARTBEAT_INTERVAL', 30),
    'max_reconnect_attempts' => 10,
    'reconnect_backoff_max' => 60,
];