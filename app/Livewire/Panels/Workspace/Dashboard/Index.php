<?php

namespace App\Livewire\Panels\Workspace\Dashboard;

use App\Models\Workspace\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $statuses = ['planning', 'doing', 'done'];

    // Modal states
    public $title;
    public $description;
    public $status = 'planning';
    public $priority = 'medium';
    public $due_at;
    public ?Task $editingTask = null;

    protected $listeners = ['refreshBoard' => '$refresh'];

    #[\Livewire\Attributes\Computed]
    public function tasks()
    {
        return Task::orderBy('order')->get();
    }

    public function sort($item, $position, $group)
    {
        $task = Task::findOrFail($item);

        $task->update([
            'status' => $group,
            'order' => $position,
        ]);

        // Optional: Re-order other tasks in the same group to maintain consistency
        $tasksInGroup = Task::where('status', $group)
            ->where('id', '!=', $item)
            ->orderBy('order')
            ->get();

        $order = 0;
        foreach ($tasksInGroup as $t) {
            if ($order == $position) {
                $order++;
            }
            $t->update(['order' => $order]);
            $order++;
        }
    }

    public function deleteTask($id)
    {
        Task::find($id)->delete();
    }

    public function openCreateModal($status = 'planning')
    {
        $this->reset(['title', 'description', 'due_at', 'editingTask']);
        $this->status = $status;
        $this->priority = 'medium';
        $this->dispatch('modal-show', id: 'create-task');
    }

    public function openEditModal(Task $task)
    {
        $this->editingTask = $task;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->status = $task->status;
        $this->priority = $task->priority;
        $this->due_at = $task->due_at?->format('Y-m-d');
        $this->dispatch('modal-show', id: 'edit-task');
    }

    public function saveTask()
    {
        $this->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,doing,done',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
        ]);

        if ($this->editingTask) {
            $this->editingTask->update([
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status,
                'priority' => $this->priority,
                'due_at' => $this->due_at,
            ]);
        } else {
            Task::create([
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status,
                'priority' => $this->priority,
                'due_at' => $this->due_at,
                'created_by' => auth()->id(),
                'order' => Task::where('status', $this->status)->count(),
            ]);
        }

        $this->dispatch('modal-close', id: $this->editingTask ? 'edit-task' : 'create-task');
        $this->reset(['title', 'description', 'status', 'priority', 'due_at', 'editingTask']);
    }

    public function statusOptions()
    {
        return [
            'planning' => __('app.tasks.statuses.planning'),
            'doing' => __('app.tasks.statuses.doing'),
            'done' => __('app.tasks.statuses.done'),
        ];
    }

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.dashboard.index');
    }
}
