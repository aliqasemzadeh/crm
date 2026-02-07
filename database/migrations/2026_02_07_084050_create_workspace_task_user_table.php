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
        Schema::create('workspace_task_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('workspace_tasks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Role of user in task
            $table->string('role', 20)->default('assignee');
            // assignee | reviewer | watcher

            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Constraints & indexes
            $table->unique(['task_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_task_user');
    }
};
