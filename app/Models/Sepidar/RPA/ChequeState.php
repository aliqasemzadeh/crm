<?php

namespace App\Models\Sepidar\RPA;

class ChequeState
{
    public static function receiptPassed(int|string|null $state): bool
    {
        return in_array((int) $state, [4, 16, 32], true);
    }

    public static function receiptLabel(int|string|null $state): string
    {
        return self::label('app.receipt_cheque_state_', $state);
    }

    public static function paymentLabel(int|string|null $state): string
    {
        return self::label('app.payment_cheque_state_', $state);
    }

    private static function label(string $prefix, int|string|null $state): string
    {
        $state = (int) $state;
        $key = $prefix.$state;
        $label = __($key);

        return $label === $key
            ? __('app.cheque_state_fallback', ['n' => $state])
            : $label;
    }
}
