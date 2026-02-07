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
        Schema::create('workspace_taskables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('workspace_tasks')
                ->cascadeOnDelete();

            // Polymorphic target
            $table->string('taskable_type');
            $table->unsignedBigInteger('taskable_id');

            $table->timestamps();

            // Prevent duplicate attachment
            $table->unique([
                'task_id',
                'taskable_type',
                'taskable_id'
            ]);

            $table->index([
                'taskable_type',
                'taskable_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_taskables');
    }
};
