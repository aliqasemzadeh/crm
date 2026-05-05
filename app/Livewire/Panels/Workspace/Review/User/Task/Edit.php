<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Edit extends Component
{
    public User $user;
    public ?Task $task = null;

    public string $title = '';
    public string $description = '';
    public string $status = 'planning';
    public string $priority = 'medium';
    public $due_at;
    public string $repeat_type = 'none';
    public ?string $repeat_weekday = null;
    public ?int $repeat_monthday = null;
    public bool $is_locked = false;

    #[On('panels.workspace.review.user.task.edit.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::findOrFail($id);
        if (! $this->task->canBeManagedBy(auth()->user())) {
            Flux::toast(__('app.task.notifications.cannot_edit_locked'), variant: 'danger');
            return;
        }
        $this->title = $this->task->title;
        $this->description = $this->task->description ?? '';
        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->due_at = $this->task->due_at?->format('Y-m-d');
        $this->repeat_type = $this->task->repeat_type ?? 'none';
        $this->repeat_weekday = $this->task->repeat_weekday !== null ? (string) $this->task->repeat_weekday : null;
        $this->repeat_monthday = $this->task->repeat_monthday;
        $this->is_locked = (bool) $this->task->is_locked;
        Flux::modal('review-user-task-edit-modal')->show();
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

    public function update()
    {
        if (! $this->task->canBeManagedBy(auth()->user())) {
            Flux::toast(__('app.task.notifications.cannot_edit_locked'), variant: 'danger');
            return;
        }

        $validated = $this->validate();
        $validated['locked_by'] = $validated['is_locked'] ? ($this->task->locked_by ?: auth()->id()) : null;
        $validated['locked_at'] = $validated['is_locked'] ? ($this->task->locked_at ?: now()) : null;

        $this->task->update($validated);
        Flux::toast(__('app.task.notifications.updated'));
        $this->dispatch('panels.workspace.review.user.board.render');
        Flux::modal('review-user-task-edit-modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.workspace.review.user.task.edit');
    }
}
