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
        Schema::connection('voip')->table('phones', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('party_phone_id');
            $table->index('is_manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('voip')->table('phones', function (Blueprint $table) {
            $table->dropIndex(['is_manual']);
            $table->dropColumn('is_manual');
        });
    }
};
