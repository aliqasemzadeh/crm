<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Create extends Component
{
    public User $user;
    public string $title = '';
    public string $description = '';
    public string $status = 'planning';
    public string $priority = 'medium';
    public $due_at;

    #[On('panels.workspace.review.user.task.create.assign-data')]
    public function assignData($status)
    {
        $this->reset(['title', 'description', 'due_at']);
        $this->status = $status;
        $this->priority = 'medium';
        Flux::modal('review-user-task-create-modal')->show();
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

    public function create()
    {
        $validated = $this->validate();
        $validated['created_by'] = auth()->id();
        $validated['source'] = 'manager';

        $task = Task::create($validated);
        $task->users()->syncWithoutDetaching([
            $this->user->id => ['role' => 'assignee', 'assigned_at' => now(), 'assigned_by' => auth()->id()]
        ]);

        Flux::toast(__('app.task.notifications.created'));
        $this->dispatch('panels.workspace.review.user.board.render');
        Flux::modal('review-user-task-create-modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.workspace.review.user.task.create');
    }
}
