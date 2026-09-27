<?php

namespace App\Services\Workspace;

use App\Jobs\Notification\BaleSendMessageJob;
use App\Models\User;
use App\Models\Workspace\TaskReport;

class TaskReportBaleNotifier
{
    public function notify(TaskReport $report): void
    {
        $report->loadMissing(['task.assignees', 'user', 'files']);

        $task = $report->task;

        if (! $task) {
            return;
        }

        $authorId = (int) $report->user_id;

        $recipients = $task->assignees
            ->filter(fn (User $user) => (int) $user->id !== $authorId)
            ->filter(fn (User $user) => filled($user->bale_code));

        if ($recipients->isEmpty()) {
            return;
        }

        $attachmentsLine = $report->files->isNotEmpty()
            ? PHP_EOL.__('app.task.activity.report_bale_attachments', [
                'count' => $report->files->count(),
            ])
            : '';

        $message = __('app.task.activity.report_bale_message', [
            'title' => $task->title,
            'author' => $report->user?->name ?? '',
            'body' => $report->body,
            'url' => route('panels.workspace.dashboard.index'),
        ]).$attachmentsLine;

        $recipients->each(function (User $user) use ($message): void {
            BaleSendMessageJob::dispatch($message, 'crm', (string) $user->bale_code);
        });
    }
}
