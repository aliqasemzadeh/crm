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
            $table->unsignedBigInteger('party_id')->nullable()->after('id');
            $table->index('party_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('voip')->table('phones', function (Blueprint $table) {
            $table->dropIndex(['party_id']);
            $table->dropColumn('party_id');
        });
    }
};
