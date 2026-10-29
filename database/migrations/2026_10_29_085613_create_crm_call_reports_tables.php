<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_call_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('top_count')->default(3);
            $table->text('summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_call_report_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_report_id')->constrained('crm_call_reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('rank');
            $table->string('phone', 32);
            $table->unsignedInteger('call_count')->default(0);
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();
            $table->boolean('has_invoice_48h')->default(false);
            $table->string('invoice_summary')->nullable();
            $table->text('identity_note')->nullable();
            $table->json('transcripts')->nullable();
            $table->json('analysis')->nullable();
            $table->string('outcome', 32)->default('pending');
            $table->timestamps();

            $table->index(['call_report_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_call_report_parties');
        Schema::dropIfExists('crm_call_reports');
    }
};
