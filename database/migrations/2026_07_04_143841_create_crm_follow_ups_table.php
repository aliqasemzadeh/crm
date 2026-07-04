<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_follow_ups', function (Blueprint $table) {
            $table->id();

            // شناسه فروشنده مسئول پیگیری (کارشناس CRM)
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');

            // شناسه مشتری که از دیتابیس سایت می‌آید
            $table->unsignedBigInteger('customer_id')->index();

            // وضعیت پیگیری
            $table->string('status', 30)->default('pending')->index();

            // دلیل عدم خرید
            $table->string('failure_reason')->nullable()->index();

            // توضیحات کامل کارشناس از تماس
            $table->text('description')->nullable();

            // تاریخ سررسید پیگیری فعلی
            $table->dateTime('due_date')->index();

            // تاریخ سررسید پیگیری بعدی
            $table->dateTime('next_follow_up_date')->nullable();

            // اگر این پیگیری خودش در نتیجه‌ی یک پیگیری دیگر ایجاد شده باشد
            $table->foreignId('parent_id')->nullable()->constrained('crm_follow_ups')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
    }
};
