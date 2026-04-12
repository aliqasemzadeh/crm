<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_requests', function (Blueprint $table) {
            $table->id();

            // شماره درخواست
            $table->string('request_number')->unique();

            // مالک درخواست
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // نوع درخواست (purchase, loan, document, etc)
            $table->string('type');

            // عنوان اختیاری
            $table->string('title')->nullable();

            // اطلاعات آیتم (چون تک آیتمی هست)
            $table->string('item_name');
            $table->text('item_description')->nullable();

            $table->integer('quantity')->default(1);
            $table->string('unit')->default('pcs');

            // قیمت
            $table->decimal('estimated_price', 12, 2);
            $table->decimal('estimated_total', 12, 2);

            // دلیل درخواست
            $table->text('reason')->nullable();

            // وضعیت کلی درخواست
            $table->string('status')->default('pending');

            // ProcessApproval integration
            $table->unsignedBigInteger('current_step')->nullable();

            // امضای درخواست‌دهنده
            $table->string('requester_signature')->nullable();

            // tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // indexes برای سرعت
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_requests');
    }
};
