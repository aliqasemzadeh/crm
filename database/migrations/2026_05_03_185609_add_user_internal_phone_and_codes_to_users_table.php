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
        Schema::table('users', function (Blueprint $table) {
            $table->string('internal_phone_id')->nullable();
            $table->string('bale_code')->nullable();
            $table->string('personnel_code')->nullable();
            $table->string('timex_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'internal_phone_id',
                'bale_code',
                'personnel_code',
                'timex_code',
            ]);
        });
    }
};
