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

        $digits = self::stripIranCountryCodePrefix($digits);

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
     * Digit strings useful for SQL LIKE / Sepidar PartyPhone.Phone matching (with or without leading 0, +98, …).
     *
     * @return list<string>
     */
    public static function digitSearchStringsForQuery(string $term): array
    {
        $digits = preg_replace('/\D+/', '', $term) ?? '';
        if ($digits === '') {
            return [];
        }

        $out = [$digits];
        $norm = self::normalize($term);
        if ($norm !== null && $norm !== '') {
            $out[] = $norm;
            $sep = self::formatForSepidar($norm);
            if ($sep !== '' && $sep !== $norm) {
                $out[] = $sep;
            }
            if (strlen($norm) === 10 && str_starts_with($norm, '9')) {
                $out[] = '98'.$norm;
                $out[] = '0098'.$norm;
            }
        }

        $stripped = ltrim($digits, '0');
        if ($stripped !== '' && $stripped !== $digits) {
            $out[] = $stripped;
        }

        $out = array_values(array_unique(array_filter($out, static fn (string $s): bool => $s !== '')));
        sort($out);

        return $out;
    }

    /**
     * Keys to match CDR / channel values against {@see Phone::$number} (canonical normalized form).
     *
     * @return list<string>
     */
    public static function lookupKeyVariants(string $raw): array
    {
        $trim = trim($raw);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $keys = [];

        foreach ([$trim, $digits] as $k) {
            if ($k !== '' && ! in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }

        $norm = self::normalize($raw);
        if ($norm !== null && $norm !== '') {
            foreach ([$norm, self::formatForSepidar($norm)] as $k) {
                if ($k !== '' && ! in_array($k, $keys, true)) {
                    $keys[] = $k;
                }
            }
            if (strlen($norm) === 10 && str_starts_with($norm, '9')) {
                foreach (['98'.$norm, '0098'.$norm] as $k) {
                    if (! in_array($k, $keys, true)) {
                        $keys[] = $k;
                    }
                }
            }

            // Landline (Shiraz 071…): 07136489347 = 7136489347 = 36489347
            foreach (self::landlineKeyVariants($norm) as $k) {
                if ($k !== '' && ! in_array($k, $keys, true)) {
                    $keys[] = $k;
                }
            }
        }

        $strippedDigits = self::stripIranCountryCodePrefix($digits);
        foreach (self::landlineKeyVariants($strippedDigits) as $k) {
            if ($k !== '' && ! in_array($k, $keys, true)) {
                $keys[] = $k;
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function landlineKeyVariants(string $digits): array
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '') {
            return [];
        }

        $digits = self::stripIranCountryCodePrefix($digits);
        $out = [];

        // 071XXXXXXXX (11) → 71XXXXXXXX + local 8
        if (str_starts_with($digits, '071') && strlen($digits) === 11) {
            $out[] = $digits;
            $out[] = substr($digits, 1);
            $out[] = substr($digits, 3);
        }

        // 71XXXXXXXX (10, not mobile 9…) → 071… + local 8
        if (str_starts_with($digits, '71') && strlen($digits) === 10 && ($digits[2] ?? '') !== '9') {
            $out[] = $digits;
            $out[] = '0'.$digits;
            $out[] = substr($digits, 2);
        }

        // Local 8-digit landline (not mobile-shaped)
        if (strlen($digits) === 8 && ! str_starts_with($digits, '9')) {
            $out[] = $digits;
            $out[] = '71'.$digits;
            $out[] = '071'.$digits;
        }

        return array_values(array_unique(array_filter($out)));
    }

    private static function stripIranCountryCodePrefix(string $digits): string
    {
        if (str_starts_with($digits, '0098')) {
            return substr($digits, 4);
        }

        if (str_starts_with($digits, '098') && strlen($digits) >= 13) {
            return substr($digits, 3);
        }

        if (str_starts_with($digits, '98') && strlen($digits) >= 12 && ($digits[2] ?? '') === '9') {
            return substr($digits, 2);
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
