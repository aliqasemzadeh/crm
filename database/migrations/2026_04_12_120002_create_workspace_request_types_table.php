<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_request_types', function (Blueprint $table) {
            $table->id();

            // شناسه داخلی نوع درخواست (purchase, loan, document)
            $table->string('name')->unique();

            // عنوان نمایشی
            $table->string('title');

            // توضیح
            $table->text('description')->nullable();

            // schema برای فرم داینامیک
            $table->json('schema')->nullable();

            // فعال / غیرفعال
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Soft delete
            $table->softDeletes();

            // index برای performance
            $table->index(['name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_request_types');
    }
};
