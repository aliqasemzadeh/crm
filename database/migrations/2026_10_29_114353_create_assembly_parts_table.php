<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_center_assembly_parts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assembly_id')->index();
            $table->bigInteger('assembly_order_part_id')->nullable()->index();
            $table->unsignedBigInteger('sepidar_item_id')->nullable()->index();
            $table->string('title');
            $table->string('serial_number')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_assembly_parts');
    }
};
