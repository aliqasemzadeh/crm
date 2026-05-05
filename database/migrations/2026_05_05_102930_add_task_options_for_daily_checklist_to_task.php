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
            $table->string('repeat_type', 20)->default('none')->after('source');
            $table->unsignedTinyInteger('repeat_weekday')->nullable()->after('repeat_type');
            $table->unsignedTinyInteger('repeat_monthday')->nullable()->after('repeat_weekday');
            $table->timestamp('last_repeated_at')->nullable()->after('repeat_monthday');
            $table->timestamp('next_repeat_at')->nullable()->after('last_repeated_at');

            $table->boolean('is_locked')->default(false)->after('next_repeat_at');
            $table->foreignId('locked_by')->nullable()->after('is_locked')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');

            $table->index(['repeat_type', 'next_repeat_at']);
            $table->index(['is_locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_tasks', function (Blueprint $table) {
            $table->dropIndex(['repeat_type', 'next_repeat_at']);
            $table->dropIndex(['is_locked']);
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn([
                'repeat_type',
                'repeat_weekday',
                'repeat_monthday',
                'last_repeated_at',
                'next_repeat_at',
                'is_locked',
                'locked_at',
            ]);
        });
    }
};
