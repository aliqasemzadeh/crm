<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use App\Models\Workspace\TaskReport;
use App\Models\Workspace\TaskFile;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Activity extends Component
{
    use WithFileUploads;

    public User $user;
    public ?Task $task = null;
    public string $body = '';
    public $files = [];

    #[On('panels.workspace.review.user.task.activity.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::with(['reports.user', 'reports.files'])->findOrFail($id);
        $this->reset(['body', 'files']);

        Flux::modal('review-user-task-activity-modal')->show();
    }

    public function send()
    {
        $this->validate([
            'body' => 'required|string|min:2',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $report = TaskReport::create([
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'type' => 'manager_note',
            'body' => $this->body,
        ]);

        if (!empty($this->files)) {
            foreach ($this->files as $file) {
                $path = $file->store('task-files', 'public');
                TaskFile::create([
                    'task_id' => $this->task->id,
                    'task_report_id' => $report->id,
                    'user_id' => auth()->id(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'disk' => 'public',
                ]);
            }
        }

        $this->reset(['body', 'files']);
        $this->task->load(['reports.user', 'reports.files']);

        Flux::toast(__('app.task.activity.report_submitted'));
    }

    public function render()
    {
        return view('livewire.panels.workspace.review.user.task.activity');
    }
}
