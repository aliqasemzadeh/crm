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

    #[On('panels.workspace.review.user.task.edit.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::findOrFail($id);
        $this->title = $this->task->title;
        $this->description = $this->task->description ?? '';
        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->due_at = $this->task->due_at?->format('Y-m-d');

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
        ];
    }

    public function update()
    {
        $validated = $this->validate();

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
