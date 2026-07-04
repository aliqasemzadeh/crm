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
        Schema::table('crm_follow_ups', function (Blueprint $table) {
            $table->boolean('satisfaction')->nullable();
            $table->boolean('warranty_satisfaction')->nullable();
            $table->boolean('colleague')->nullable();
            $table->boolean('resale')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_follow_ups', function (Blueprint $table) {
            $table->dropColumn(['satisfaction', 'warranty_satisfaction', 'colleague', 'resale']);
        });
    }
};
