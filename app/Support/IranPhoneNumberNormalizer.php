<?php

namespace App\Support;

use App\Console\Commands\Voip\ImportPhoneFromSepidarCommand;

/**
 * Canonical VoIP / CRM phone keys (matches {@see ImportPhoneFromSepidarCommand}).
 */
final class IranPhoneNumberNormalizer
{
    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '' || strlen($digits) < 5) {
            return null;
        }

        if (str_starts_with($digits, '09')) {
            return substr($digits, 1);
        }

        if (str_starts_with($digits, '071')) {
            return substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Display / Sepidar PartyPhone.Phone value for typical Iranian mobiles (09…).
     */
    public static function formatForSepidar(string $normalized): string
    {
        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) === 10 && str_starts_with($normalized, '9')) {
            return '0'.$normalized;
        }

        return $normalized;
    }

    public static function displayForUi(string $normalized): string
    {
        return self::formatForSepidar($normalized);
    }
}
