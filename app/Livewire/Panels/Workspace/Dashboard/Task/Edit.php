<?php

namespace App\Livewire\Panels\Workspace\Dashboard\Task;

use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Edit extends Component
{
    public Task $task;

    public string $title = '';
    public string $description = '';
    public string $status = '';
    public string $priority = '';
    public $due_at;

    #[On('panels.workspace.dashboard.task.edit.assign-data')]
    public function assignData($id)
    {

        $this->task = Task::findOrFail($id);
        $this->title = $this->task->title;
        $this->description = $this->task->description ?? '';
        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->due_at = $this->task->due_at?->format('Y-m-d');

        Flux::modal('panels.workspace.dashboard.task.edit.modal')->show();
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

    public function edit()
    {
        $validated = $this->validate();

        $this->task->update($validated);

        Flux::toast(__('app.task.notifications.updated'));

        $this->dispatch('panels.workspace.dashboard.index.render');
    }

    public function render()
    {
        return view('livewire.panels.workspace.dashboard.task.edit');
    }
}
