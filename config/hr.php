<?php

return [
    'allowed_ips' => ['192.168.3.*'],
    'allowed_domains' => ['crm.test'],
    'check_mode' => 'ip', // 'ip', 'domain', 'both' — regular users: internal network only
];
