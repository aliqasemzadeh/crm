<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_center_assembly_order_parts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assembly_order_id')->index('scaop_order_id_idx');
            $table->unsignedBigInteger('sepidar_item_id')->nullable()->index('scaop_item_id_idx');
            $table->unsignedBigInteger('sepidar_invoice_item_id')->nullable()->index('scaop_inv_item_id_idx');
            $table->string('title');
            $table->unsignedInteger('quantity_per_unit')->default(1);
            $table->boolean('requires_serial')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_assembly_order_parts');
    }
};
