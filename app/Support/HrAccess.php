<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HrAccess
{
    public static function isAllowed(?Request $request = null): bool
    {
        $user = Auth::user();

        if ($user?->hasRole('admin')) {
            return true;
        }

        $request ??= request();
        $config = config('hr');
        $ip = $request->ip();
        $host = $request->getHost();
        $mode = $config['check_mode'] ?? 'ip';

        $ipAllowed = self::checkIp($ip, $config['allowed_ips'] ?? []);
        $domainAllowed = in_array($host, $config['allowed_domains'] ?? [], true);

        return match ($mode) {
            'ip' => $ipAllowed,
            'domain' => $domainAllowed,
            'both' => $ipAllowed || $domainAllowed,
            default => false,
        };
    }

    public static function checkIp(string $ip, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^'.str_replace(['*', '.'], ['[0-9]+', '\\.'], $pattern).'$/';

                if (preg_match($regex, $ip)) {
                    return true;
                }
            } elseif ($ip === $pattern) {
                return true;
            }
        }

        return false;
    }
}
