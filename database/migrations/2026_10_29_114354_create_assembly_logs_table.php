<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_center_assembly_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assembly_order_id')->nullable()->index();
            $table->bigInteger('assembly_id')->nullable()->index();
            $table->bigInteger('user_id')->nullable()->index();
            $table->string('type')->default('note')->index();
            $table->string('status')->nullable();
            $table->longText('description');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_assembly_logs');
    }
};
