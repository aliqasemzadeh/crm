<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_invoice_edit_watermarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sepidar_invoice_id')->unique();
            $table->dateTime('last_modification_date')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_invoice_edit_watermarks');
    }
};
