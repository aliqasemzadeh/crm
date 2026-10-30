<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('service_center_assembly_orders');

        Schema::create('service_center_assembly_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('sepidar_invoice_id')->nullable()->index();
            $table->string('invoice_number')->nullable()->index();
            $table->unsignedBigInteger('customer_party_ref')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('pending')->index();
            $table->bigInteger('ordered_by_user_id')->nullable()->index();
            $table->longText('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_center_assembly_orders');
    }
};
