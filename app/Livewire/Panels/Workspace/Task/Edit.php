<?php

namespace App\Livewire\Panels\Workspace\Task;

use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Edit extends Component
{
    public Task $task;

    public string $title = '';
    public string $description = '';
    public string $status = '';
    public string $priority = '';
    public $due_at;
    public string $repeat_type = 'none';
    public ?string $repeat_weekday = null;
    public ?int $repeat_monthday = null;
    public bool $is_locked = false;

    public function mount(Task $task)
    {
        if ($task->created_by !== auth()->id() && ! auth()->user()?->hasRole('administrator')) {
            abort(403);
        }

        if ($task->approval_status === 'approved') {
            Flux::toast(__('app.task.notifications.cannot_edit_approved'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        if (! $task->canBeManagedBy(auth()->user())) {
            Flux::toast(__('app.task.notifications.cannot_edit_locked'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        $this->task = $task;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->status = $task->status;
        $this->priority = $task->priority;
        $this->due_at = $task->due_at?->format('Y-m-d');
        $this->repeat_type = $task->repeat_type ?? 'none';
        $this->repeat_weekday = $task->repeat_weekday !== null ? (string) $task->repeat_weekday : null;
        $this->repeat_monthday = $task->repeat_monthday;
        $this->is_locked = (bool) $task->is_locked;
    }

    protected function rules()
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,doing,done',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
            'repeat_type' => 'required|in:none,daily,weekly,monthly',
            'repeat_weekday' => 'nullable|required_if:repeat_type,weekly|integer|between:0,6',
            'repeat_monthday' => 'nullable|required_if:repeat_type,monthly|integer|between:1,31',
            'is_locked' => 'boolean',
        ];
    }

    public function save()
    {
        if ($this->task->approval_status === 'approved') {
            Flux::toast(__('app.task.notifications.cannot_edit_approved'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        if (! $this->task->canBeManagedBy(auth()->user())) {
            Flux::toast(__('app.task.notifications.cannot_edit_locked'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        $validated = $this->validate();
        $validated['locked_by'] = $validated['is_locked'] ? ($this->task->locked_by ?: auth()->id()) : null;
        $validated['locked_at'] = $validated['is_locked'] ? ($this->task->locked_at ?: now()) : null;

        $this->task->update($validated);
        Flux::toast(__('app.task.notifications.updated'));

        return $this->redirect(route('panels.workspace.task.index'), navigate: true);
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.task.edit');
    }
}
