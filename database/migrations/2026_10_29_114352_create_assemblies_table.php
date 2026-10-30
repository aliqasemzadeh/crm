<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_center_assemblies', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assembly_order_id')->index();
            $table->unsignedInteger('unit_number')->default(1);
            $table->string('system_serial')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->bigInteger('assembled_by_user_id')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assembly_order_id', 'unit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_assemblies');
    }
};
