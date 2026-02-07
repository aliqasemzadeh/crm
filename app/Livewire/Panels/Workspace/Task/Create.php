<?php

namespace App\Livewire\Panels\Workspace\Task;

use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\Layout;
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

    public function save()
    {
        $validated = $this->validate();
        $validated['created_by'] = auth()->id();

        Task::create($validated);

        Flux::toast(__('app.task.notifications.created'));

        return $this->redirect(route('workspace.tasks.index'), navigate: true);
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.task.create');
    }
}
