<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_warranty_shipments', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('item_title');
            $table->string('serial_number')->nullable()->after('quantity');

            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_warranty_shipments', function (Blueprint $table) {
            $table->dropIndex(['serial_number']);
            $table->dropColumn(['quantity', 'serial_number']);
        });
    }
};
