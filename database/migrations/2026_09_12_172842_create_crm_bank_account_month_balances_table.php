<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_bank_account_month_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fiscal_year_ref')->index();
            $table->unsignedSmallInteger('jalali_year');
            $table->unsignedTinyInteger('jalali_month');
            $table->unsignedInteger('bank_account_id');
            $table->string('account_label');
            $table->decimal('ending_balance', 20, 2)->default(0);
            $table->decimal('min_balance', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['fiscal_year_ref', 'jalali_year', 'jalali_month', 'bank_account_id'],
                'crm_bank_month_balances_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_bank_account_month_balances');
    }
};
