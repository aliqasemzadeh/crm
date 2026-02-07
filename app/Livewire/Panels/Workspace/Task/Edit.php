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

    public function mount(Task $task)
    {
        if ($task->created_by !== auth()->id()) {
            abort(403);
        }

        if ($task->approval_status === 'approved') {
            Flux::toast(__('app.task.notifications.cannot_edit_approved'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        $this->task = $task;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->status = $task->status;
        $this->priority = $task->priority;
        $this->due_at = $task->due_at?->format('Y-m-d');
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

    public function save()
    {
        if ($this->task->approval_status === 'approved') {
            Flux::toast(__('app.task.notifications.cannot_edit_approved'), variant: 'danger');
            return $this->redirect(route('panels.workspace.task.index'), navigate: true);
        }

        $validated = $this->validate();

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
