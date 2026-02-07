<?php

namespace App\Livewire\Panels\Workspace\Dashboard\Task;

use App\Models\Workspace\Task;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;

class Create extends Component
{
    public string $title = '';
    public string $description = '';
    public string $status = 'planning';
    public string $priority = 'medium';
    public $due_at;

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

        $task = Task::create($validated);
        $task->users()->syncWithoutDetaching([['role' => 'reviewer', 'assigned_at' => now(), 'assigned_by' => auth()->id()]]);

        Flux::toast(__('app.task.notifications.created'));
        $this->dispatch('panels.workspace.dashboard.index.render');
    }

    public function render()
    {
        return view('livewire.panels.workspace.dashboard.task.create');
    }
}
