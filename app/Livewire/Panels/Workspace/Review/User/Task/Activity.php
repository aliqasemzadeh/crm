<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use App\Models\Workspace\TaskChecklist;
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
    public array $checklistItems = [];
    public string $newChecklistTitle = '';

    #[On('panels.workspace.review.user.task.activity.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::with(['reports.user', 'reports.files', 'checklists'])->findOrFail($id);
        $this->syncChecklistItems();
        $this->reset(['body', 'files', 'newChecklistTitle']);

        Flux::modal('review-user-task-activity-modal')->show();
    }

    #[On('panels.workspace.review.user.task.activity.checklist.assign-data')]
    public function assignChecklistData($id): void
    {
        $this->assignData($id);
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
        Flux::modal('panels.workspace.review.user.task.activity.checklist.create.modal')->close();
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
        return view('livewire.panels.workspace.review.user.task.activity');
    }
}
