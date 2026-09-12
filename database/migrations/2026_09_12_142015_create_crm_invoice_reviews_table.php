<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_invoice_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sepidar_invoice_id')->unique();
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('customer_party_ref')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->dateTime('invoice_date')->nullable();
            $table->decimal('invoice_net_price', 20, 2)->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('balance_snapshot')->nullable();
            $table->json('stock_snapshot')->nullable();
            $table->timestamp('snapshot_at')->nullable();
            $table->unsignedBigInteger('accounting_reviewed_by')->nullable()->index();
            $table->timestamp('accounting_reviewed_at')->nullable();
            $table->text('accounting_note')->nullable();
            $table->string('warehouse_status')->nullable()->index();
            $table->unsignedBigInteger('warehouse_handled_by')->nullable()->index();
            $table->timestamp('warehouse_handled_at')->nullable();
            $table->text('warehouse_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_invoice_reviews');
    }
};
