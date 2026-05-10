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
        Schema::table('workspace_tasks', function (Blueprint $table) {
            $table->foreignId('generated_from_task_id')
                ->nullable()
                ->after('locked_at')
                ->constrained('workspace_tasks')
                ->nullOnDelete();

            $table->timestamp('starts_at')->nullable()->after('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_tasks', function (Blueprint $table) {
            $table->dropForeign(['generated_from_task_id']);
            $table->dropColumn(['generated_from_task_id', 'starts_at']);
        });
    }
};
