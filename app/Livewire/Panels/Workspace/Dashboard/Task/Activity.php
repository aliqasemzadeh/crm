<?php

namespace App\Livewire\Panels\Workspace\Dashboard\Task;

use Livewire\Component;

use App\Models\Workspace\Task;
use App\Models\Workspace\TaskChecklist;
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
    public array $checklistItems = [];
    public string $newChecklistTitle = '';

    #[On('panels.workspace.dashboard.task.activity.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::with(['reports.user', 'reports.files', 'checklists'])->findOrFail($id);
        $this->syncChecklistItems();
        $this->reset(['body', 'files', 'newChecklistTitle']);

        Flux::modal('panels.workspace.dashboard.task.activity.modal')->show();
    }

    #[On('panels.workspace.dashboard.task.activity.checklist.assign-data')]
    public function assignChecklistData($id): void
    {
        $this->assignData($id);
        Flux::modal('panels.workspace.dashboard.task.activity.checklist.create.modal')->show();
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
            'type' => 'worklog',
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
        $this->task->load(['reports.user', 'reports.files', 'checklists']);
        $this->syncChecklistItems();

        Flux::toast(__('app.task.activity.report_submitted'));
    }

    public function addChecklistItem(): void
    {
        if (! $this->task) {
            return;
        }

        $title = trim($this->newChecklistTitle);
        if ($title === '') {
            Flux::toast(__('app.task.checklist_title_required'), variant: 'danger');
            return;
        }

        $this->task->checklists()->create([
            'title' => $title,
            'order' => count($this->checklistItems) + 1,
        ]);

        $this->newChecklistTitle = '';
        $this->task->load('checklists');
        $this->syncChecklistItems();
        Flux::modal('panels.workspace.dashboard.task.activity.checklist.create.modal')->close();
        Flux::toast(__('app.task.notifications.updated'));
    }

    public function removeChecklistItem(string $itemId): void
    {
        if (! $this->task) {
            return;
        }

        TaskChecklist::query()
            ->where('task_id', $this->task->id)
            ->whereKey($itemId)
            ->delete();

        $this->task->load('checklists');
        $this->syncChecklistItems();
    }

    public function sortChecklist($itemId, $position): void
    {
        $items = collect($this->checklistItems);
        $currentIndex = $items->search(fn ($item) => (string) $item['id'] === (string) $itemId);
        if ($currentIndex === false) {
            return;
        }

        $item = $items->pull($currentIndex);
        $items->splice((int) $position, 0, [$item]);
        $this->checklistItems = $items->values()->all();

        foreach ($items->values() as $index => $checklist) {
            TaskChecklist::query()
                ->where('task_id', $this->task->id)
                ->whereKey($checklist['id'])
                ->update(['order' => $index + 1]);
        }

        $this->task->load('checklists');
        $this->syncChecklistItems();
    }

    public function toggleChecklist(int $checklistId): void
    {
        if (! $this->task) {
            return;
        }

        $checklist = TaskChecklist::query()
            ->where('task_id', $this->task->id)
            ->findOrFail($checklistId);

        $isDone = ! $checklist->is_done;
        $checklist->update([
            'is_done' => $isDone,
            'done_at' => $isDone ? now() : null,
            'done_by' => $isDone ? auth()->id() : null,
        ]);

        $this->task->load(['reports.user', 'reports.files', 'checklists.doneBy']);
        $this->syncChecklistItems();
    }

    protected function syncChecklistItems(): void
    {
        $this->checklistItems = $this->task?->checklists
            ?->map(fn ($checklist) => [
                'id' => (string) $checklist->id,
                'title' => $checklist->title,
            ])
            ->values()
            ->all() ?? [];
    }

    public function render()
    {
        return view('livewire.panels.workspace.dashboard.task.activity');
    }
}
