<?php

namespace App\Livewire\Panels\Workspace\Task;

use App\Models\Workspace\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function deleteTask(Task $task)
    {
        if ($task->created_by !== auth()->id()) {
            return;
        }

        $task->delete();
        $this->dispatch('task-deleted');
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        $tasks = Task::where('created_by', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.panels.workspace.task.index', [
            'tasks' => $tasks
        ]);
    }
}
