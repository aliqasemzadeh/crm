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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('title', 200);
            $table->text('description')->nullable();

            // Kanban (3 ستون)
            $table->string('status', 20)->default('planning');
            // planning | doing | done

            // Review / Approval
            $table->string('approval_status', 20)->default('none');
            // none | pending | approved | rejected
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_note')->nullable();

            // Meta
            $table->string('priority', 20)->default('medium');
            // low | medium | high | urgent
            $table->timestamp('due_at')->nullable();

            // Creator / Source
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('user');
            // user | system | manager

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'approval_status']);
            $table->index(['due_at']);
            $table->index(['created_by']);
            $table->index(['approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
