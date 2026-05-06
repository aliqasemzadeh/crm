<?php

namespace App\Console\Commands\Workspace;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Workspace\Task;
use App\Models\Workspace\TaskChecklist;
use Illuminate\Console\Command;

class GenerateRecurringTasksCommand extends Command
{
    protected $signature = 'app:workspace:generate-recurring-tasks';

    protected $description = 'Generate recurring workspace tasks and notify assignees';

    public function handle(): int
    {
        $now = now();

        $templates = Task::query()
            ->whereIn('repeat_type', ['daily', 'weekly', 'monthly'])
            ->where(function ($query) use ($now) {
                $query->whereNull('next_repeat_at')->orWhere('next_repeat_at', '<=', $now);
            })
            ->with(['users', 'checklists'])
            ->get();

        foreach ($templates as $template) {
            $nextAt = $this->resolveNextRepeatAt($template, $now);
            if (! $nextAt) {
                continue;
            }

            $task = Task::create([
                'title' => $template->title,
                'description' => $template->description,
                'status' => 'planning',
                'order' => 0,
                'approval_status' => 'none',
                'priority' => $template->priority,
                'due_at' => $nextAt,
                'created_by' => $template->created_by,
                'source' => 'system',
                'repeat_type' => 'none',
                'is_locked' => $template->is_locked,
                'locked_by' => $template->locked_by,
                'locked_at' => $template->locked_at,
            ]);

            if ($template->users->isNotEmpty()) {
                $syncData = [];
                foreach ($template->users as $user) {
                    $syncData[$user->id] = [
                        'role' => $user->pivot->role,
                        'assigned_at' => now(),
                        'assigned_by' => $template->created_by,
                    ];
                }
                $task->users()->sync($syncData);
            }

            if ($template->checklists->isNotEmpty()) {
                $ts = now();
                $rows = [];
                foreach ($template->checklists as $checklist) {
                    $rows[] = [
                        'task_id' => $task->id,
                        'title' => $checklist->title,
                        // Keep template ordering; don't renumber on copy.
                        'order' => (int) ($checklist->order ?? 0),
                        // New task: checklist starts as not-done.
                        'is_done' => false,
                        'done_at' => null,
                        'done_by' => null,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ];
                }

                TaskChecklist::query()->insert($rows);
            }

            foreach ($task->assignees as $assignee) {
                if (blank($assignee->mobile)) {
                    continue;
                }
                SendSmsMessageJob::dispatch(
                    $assignee->mobile,
                    __('app.task.notifications.recurring_created_sms', ['title' => $task->title])
                );
            }

            $template->update([
                'last_repeated_at' => $now,
                'next_repeat_at' => $this->resolveNextRepeatAt($template, $nextAt),
            ]);
        }

        return self::SUCCESS;
    }

    private function resolveNextRepeatAt(Task $task, $from)
    {
        if ($task->repeat_type === 'daily') {
            return $from->copy()->addDay()->startOfDay();
        }

        if ($task->repeat_type === 'weekly') {
            $weekdayMap = [0 => 6, 1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5];
            $weekday = $weekdayMap[(int) ($task->repeat_weekday ?? 0)] ?? 6;
            $daysUntil = ($weekday - (int) $from->dayOfWeek + 7) % 7;
            $daysUntil = $daysUntil === 0 ? 7 : $daysUntil;

            return $from->copy()->addDays($daysUntil)->startOfDay();
        }

        if ($task->repeat_type === 'monthly') {
            $monthDay = (int) ($task->repeat_monthday ?? 1);
            $target = $from->copy()->addMonthNoOverflow()->startOfMonth();
            $safeDay = min($monthDay, $target->daysInMonth);

            return $target->day($safeDay)->startOfDay();
        }

        return null;
    }
}
