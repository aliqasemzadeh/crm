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
        Schema::create('workspace_task_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('workspace_tasks')
                ->cascadeOnDelete();

            $table->foreignId('task_report_id')
                ->constrained('workspace_task_reports')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type', 30)->default('worklog');
            // worklog | comment | review | system

            $table->text('body'); // "چه کاری انجام دادم" یا توضیح

            // اختیاری ولی خیلی مفید برای آینده:
            $table->unsignedInteger('spent_minutes')->nullable(); // مدت زمان
            $table->json('meta')->nullable(); // old/new status, attachments count, etc.

            $table->timestamps();

            $table->index(['task_id', 'type']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_task_reports');
    }
};
