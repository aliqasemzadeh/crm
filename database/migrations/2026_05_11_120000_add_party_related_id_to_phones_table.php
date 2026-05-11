<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('voip')->table('phones', function (Blueprint $table) {
            $table->unsignedBigInteger('party_related_id')->nullable()->after('party_phone_id');
            $table->index('party_related_id');
        });
    }

    public function down(): void
    {
        Schema::connection('voip')->table('phones', function (Blueprint $table) {
            $table->dropIndex(['party_related_id']);
            $table->dropColumn('party_related_id');
        });
    }
};
