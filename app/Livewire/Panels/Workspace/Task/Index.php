<?php

namespace App\Livewire\Panels\Workspace\Task;

use App\Models\Workspace\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $filter_status = '';
    public $filter_approval_status = '';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filter_status', 'filter_approval_status'])) {
            $this->resetPage();
        }
    }

    public function deleteTask(Task $task)
    {
        if ($task->created_by !== auth()->id() || $task->status === 'done' || $task->approval_status === 'approved') {
            return;
        }

        $task->delete();
        $this->dispatch('task-deleted');
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        $query = Task::where('created_by', auth()->id());

        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_approval_status) {
            $query->where('approval_status', $this->filter_approval_status);
        }

        $tasks = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.panels.workspace.task.index', [
            'tasks' => $tasks
        ]);
    }
}
