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
        Schema::create('workspace_task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('workspace_tasks')->cascadeOnDelete();
            $table->string('title', 200);
            $table->integer('order')->default(0);
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['task_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_task_checklists');
    }
};
