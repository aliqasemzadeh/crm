<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add(
            'invoice.default_stock_ref',
            (int) config('sepidar.DefaultStockRef', 2)
        );
        $this->migrator->add('invoice.show_stock_selector', false);
    }

    public function down(): void
    {
        $this->migrator->delete('invoice.default_stock_ref');
        $this->migrator->delete('invoice.show_stock_selector');
    }
};
