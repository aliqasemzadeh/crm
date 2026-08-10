<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crm_cash_back_rules', function (Blueprint $table) {
            $table->text('sms_text')->nullable()->after('usage_duration_days');
            $table->string('site_url')->nullable()->after('sms_text');
        });

        $defaultSms = __('app.cash_back_sms');
        $defaultSiteUrl = 'https://setaregan.co';

        DB::table('crm_cash_back_rules')
            ->whereNull('sms_text')
            ->update([
                'sms_text' => $defaultSms,
                'site_url' => $defaultSiteUrl,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cash_back_rules', function (Blueprint $table) {
            $table->dropColumn(['sms_text', 'site_url']);
        });
    }
};
