<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_center_repairs', function (Blueprint $table) {
            $table->string('short_code', 16)->nullable()->unique()->after('admission_code');
        });
    }

    public function down(): void
    {
        Schema::table('service_center_repairs', function (Blueprint $table) {
            $table->dropUnique(['short_code']);
            $table->dropColumn('short_code');
        });
    }
};
