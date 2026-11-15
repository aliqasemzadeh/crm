<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InvoiceSettings extends Settings
{
    public int $default_stock_ref;

    public bool $show_stock_selector;

    public static function group(): string
    {
        return 'invoice';
    }

    public function resolvedDefaultStockRef(): int
    {
        return $this->default_stock_ref > 0
            ? $this->default_stock_ref
            : (int) config('sepidar.DefaultStockRef', 2);
    }
}
