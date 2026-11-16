<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_warranty_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('item_title');
            $table->string('warranty_company');
            $table->date('sent_at');
            $table->date('received_at')->nullable();
            $table->string('status')->default('sent');
            $table->text('description')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'sent_at']);
            $table->index('warranty_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_warranty_shipments');
    }
};
