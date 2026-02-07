<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_center_repairs', function (Blueprint $table) {
            $table->bigInteger('admission_counter')->default(1)->after('id');
            $table->string('admission_code')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_center_repairs', function (Blueprint $table) {
            $table->dropColumn('admission_counter');
            $table->dropColumn('admission_code');
        });
    }
};
