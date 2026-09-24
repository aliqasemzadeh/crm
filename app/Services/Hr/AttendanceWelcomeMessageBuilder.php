<?php

namespace App\Services\Hr;

use App\Models\Crm\FollowUp;
use App\Models\Hr\Record;
use App\Models\User;
use Morilog\Jalali\Jalalian;

class AttendanceWelcomeMessageBuilder
{
    private const TASK_LIMIT = 10;

    public function build(User $user, Record $record): string
    {
        $lines = [
            __('app.hr_attendance_welcome_bale_header', [
                'name' => $user->name,
                'time' => Jalalian::fromDateTime($record->recorded_at)->format('H:i'),
            ]),
            '',
        ];

        $tasks = $user->tasks()
            ->where('approval_status', '!=', 'approved')
            ->whereIn('status', ['planning', 'doing'])
            ->orderByRaw("CASE status WHEN 'planning' THEN 1 WHEN 'doing' THEN 2 ELSE 99 END")
            ->orderBy('order')
            ->orderBy('workspace_tasks.id')
            ->limit(self::TASK_LIMIT + 1)
            ->get(['workspace_tasks.id', 'title', 'status', 'due_at']);

        if ($tasks->isEmpty()) {
            $lines[] = __('app.hr_attendance_welcome_bale_no_tasks');
        } else {
            $displayCount = min($tasks->count(), self::TASK_LIMIT);
            $lines[] = __('app.hr_attendance_welcome_bale_tasks_title', [
                'count' => $displayCount,
            ]);

            foreach ($tasks->take(self::TASK_LIMIT) as $task) {
                $due = $task->due_at
                    ? __('app.hr_attendance_welcome_bale_task_due', [
                        'date' => Jalalian::fromDateTime($task->due_at)->format('Y/m/d'),
                    ])
                    : '';

                $lines[] = __('app.hr_attendance_welcome_bale_task_line', [
                    'title' => $task->title,
                    'status' => __('app.statuses.'.$task->status),
                    'due' => $due,
                ]);
            }

            if ($tasks->count() > self::TASK_LIMIT) {
                $lines[] = __('app.hr_attendance_welcome_bale_tasks_more');
            }
        }

        $followUps = FollowUp::query()->forAgent((int) $user->id)->todayPending()->count();
        $reviews = $user->reviewTasks()->where('approval_status', 'pending')->count();

        $lines[] = '';
        $lines[] = __('app.hr_attendance_welcome_bale_reminder_title');
        $lines[] = __('app.hr_attendance_welcome_bale_reminder_clock_out');
        $lines[] = __('app.hr_attendance_welcome_bale_reminder_tasks', [
            'url' => route('panels.workspace.dashboard.index'),
        ]);

        if ($followUps > 0) {
            $lines[] = __('app.hr_attendance_welcome_bale_reminder_follow_ups', [
                'count' => $followUps,
                'url' => route('panels.crm.follow-up.index'),
            ]);
        }

        if ($reviews > 0) {
            $lines[] = __('app.hr_attendance_welcome_bale_reminder_reviews', [
                'count' => $reviews,
            ]);
        }

        return implode("\n", $lines);
    }
}
