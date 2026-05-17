<?php

namespace App\Console\Commands\Workspace;

use App\Jobs\Notification\SendSmsMessageJob;
use App\Models\Workspace\Task;
use App\Models\Workspace\TaskChecklist;
use Carbon\Carbon;
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
            $nextDay = $this->resolveNextRepeatAt($template, $now);
            if (! $nextDay) {
                continue;
            }

            $nextScheduled = $this->resolveNextRepeatAt($template, $nextDay);

            $dayStart = $nextDay->copy()->startOfDay();
            $startsAt = $dayStart->copy()->setTime(8, 0);
            $dueAt = $dayStart->copy()->setTime(23, 55);

            $task = Task::create([
                'title' => $template->title,
                'description' => $template->description,
                'status' => 'planning',
                'order' => 0,
                'approval_status' => 'none',
                'approved_by' => null,
                'approved_at' => null,
                'review_note' => null,
                'priority' => $template->priority,
                'starts_at' => $startsAt,
                'due_at' => $dueAt,
                'done_at' => null,
                'created_by' => $template->created_by,
                'source' => 'system',
                'repeat_type' => $template->repeat_type,
                'repeat_weekday' => $template->repeat_weekday,
                'repeat_monthday' => $template->repeat_monthday,
                'generated_from_task_id' => $template->id,
                'next_repeat_at' => $nextScheduled,
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
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
                        'order' => (int) ($checklist->order ?? 0),
                        'is_done' => false,
                        'done_at' => null,
                        'done_by' => null,
                        'deleted_at' => null,
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
                    __('app.task.notifications.recurring_created_sms', [
                        'name' => $assignee->name,
                        'title' => $task->title,
                    ])
                );
            }

            $template->update([
                'repeat_type' => 'none',
                'repeat_weekday' => null,
                'repeat_monthday' => null,
                'last_repeated_at' => $now,
                'next_repeat_at' => null,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Next calendar occurrence (start of day). Daily/weekly skip Friday unless monthly.
     */
    private function resolveNextRepeatAt(Task $task, Carbon $from): ?Carbon
    {
        if ($task->repeat_type === 'daily') {
            $next = $from->copy()->addDay()->startOfDay();

            return $this->skipFridayUnlessMonthly($next, $task->repeat_type);
        }

        if ($task->repeat_type === 'weekly') {
            $weekdayMap = [0 => 6, 1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5];
            $weekday = $weekdayMap[(int) ($task->repeat_weekday ?? 0)] ?? 6;
            $daysUntil = ($weekday - (int) $from->dayOfWeek + 7) % 7;
            $daysUntil = $daysUntil === 0 ? 7 : $daysUntil;

            $next = $from->copy()->addDays($daysUntil)->startOfDay();

            return $this->skipFridayUnlessMonthly($next, $task->repeat_type);
        }

        if ($task->repeat_type === 'monthly') {
            $monthDay = (int) ($task->repeat_monthday ?? 1);
            $target = $from->copy()->addMonthNoOverflow()->startOfMonth();
            $safeDay = min($monthDay, $target->daysInMonth);

            return $target->copy()->day($safeDay)->startOfDay();
        }

        return null;
    }

    private function skipFridayUnlessMonthly(Carbon $date, string $repeatType): Carbon
    {
        $d = $date->copy()->startOfDay();

        if ($repeatType === 'monthly') {
            return $d;
        }

        while ((int) $d->dayOfWeek === Carbon::FRIDAY) {
            $d->addDay();
        }

        return $d;
    }
}
