<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Previously a generated child kept repeat settings while the parent also stayed recurring,
 * which could schedule duplicates. Parents that already spawned a child must not drive recurrence.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('workspace_tasks')
            ->whereIn('id', function ($query) {
                $query->select('generated_from_task_id')
                    ->from('workspace_tasks')
                    ->whereNotNull('generated_from_task_id');
            })
            ->whereIn('repeat_type', ['daily', 'weekly', 'monthly'])
            ->update([
                'repeat_type' => 'none',
                'repeat_weekday' => null,
                'repeat_monthday' => null,
                'next_repeat_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Cannot restore previous repeat settings reliably.
    }
};
