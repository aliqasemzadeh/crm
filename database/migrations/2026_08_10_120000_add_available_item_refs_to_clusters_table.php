<?php

use App\Models\Sepidar\Local\INV\Cluster;
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
        Schema::table('clusters', function (Blueprint $table) {
            $table->json('available_item_refs')->nullable()->after('item_refs');
        });

        Cluster::query()->each(function (Cluster $cluster): void {
            $cluster->update([
                'available_item_refs' => $cluster->item_refs ?? [],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clusters', function (Blueprint $table) {
            $table->dropColumn('available_item_refs');
        });
    }
};
