<?php

namespace App\Livewire\Panels\Workspace\Review\Task;

use Livewire\Component;

use App\Models\Workspace\Task;
use App\Models\Workspace\TaskReport;
use App\Models\Workspace\TaskFile;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class Activity extends Component
{
    use WithFileUploads;

    public ?Task $task = null;
    public string $body = '';
    public $files = [];

    public $review_note;
    public $approval_status;

    #[On('panels.workspace.review.task.activity.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::with(['reports.user', 'reports.files'])->findOrFail($id);
        $this->review_note = $this->task->review_note;
        $this->approval_status = $this->task->approval_status === 'none' ? 'pending' : $this->task->approval_status;
        $this->reset(['body', 'files']);

        Flux::modal('panels.workspace.review.task.activity.modal')->show();
    }

    public function approve()
    {
        $this->approval_status = 'approved';
        $this->submitReview();
    }

    public function reject()
    {
        $this->validate([
            'review_note' => 'required|string|min:3',
        ], [
            'review_note.required' => __('app.rejection_reason_required'),
        ]);

        $this->approval_status = 'rejected';
        $this->submitReview();
    }

    protected function submitReview()
    {
        $updateData = [
            'approval_status' => $this->approval_status,
            'review_note' => $this->review_note,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ];

        if ($this->approval_status === 'rejected') {
            $updateData['status'] = 'doing';
        }

        $this->task->update($updateData);

        Flux::modal('panels.workspace.review.task.activity.modal')->close();
        Flux::toast(__('app.task.review.success_message'));
        $this->dispatch('panels.workspace.review.index.render');
    }

    public function send()
    {
        if ($this->task->status === 'done' && $this->task->approval_status === 'approved') {
            Flux::toast(__('app.task.activity.cannot_add_to_approved_task'), variant: 'danger');
            return;
        }

        $this->validate([
            'body' => 'required|string|min:2',
            'files.*' => 'nullable|file|max:10240', // 10MB
        ]);

        $report = TaskReport::create([
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
            'type' => 'review',
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
        return view('livewire.panels.workspace.review.task.activity');
    }
}
