<?php

return [

    'call_report' => [
        'top_count' => (int) env('CRM_CALL_REPORT_TOP_COUNT', 3),
        'invoice_lookback_hours' => (int) env('CRM_CALL_REPORT_INVOICE_LOOKBACK_HOURS', 48),
        'max_recordings_per_phone' => (int) env('CRM_CALL_REPORT_MAX_RECORDINGS', 3),
        'min_billsec' => (int) env('CRM_CALL_REPORT_MIN_BILLSEC', 15),
        'recordings_base_path' => env('ISSABEL_RECORDINGS_BASE_PATH', '/var/spool/asterisk/monitor'),
        // Optional Windows/SMB mount of Issabel monitor folder (checked first)
        'local_mount_path' => env('ISSABEL_RECORDINGS_LOCAL_PATH', ''),
        'sftp' => [
            'host' => env('ISSABEL_SFTP_HOST', '192.168.3.101'),
            'port' => (int) env('ISSABEL_SFTP_PORT', 22),
            'username' => env('ISSABEL_SFTP_USERNAME'),
            'password' => env('ISSABEL_SFTP_PASSWORD'),
            'timeout' => (int) env('ISSABEL_SFTP_TIMEOUT', 30),
        ],
    ],

];
