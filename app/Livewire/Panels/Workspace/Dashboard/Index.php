<?php

namespace App\Livewire\Panels\Workspace\Dashboard;

use App\Models\Workspace\Task;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    public $statuses = ['planning', 'doing', 'done'];

    public $title;
    public $description;
    public $status = 'planning';
    public $priority = 'medium';
    public $due_at;
    public ?Task $editingTask = null;

    protected $listeners = ['refreshBoard' => '$refresh'];

    #[Computed]
    public function tasks()
    {
        // مهم: اول status بعد order تا نتایج مرتب باشد
        return Task::query()
            ->orderBy('order')
            ->get();
    }

    // ✅ برای هر ستون یک handler (مقصد از روی متد مشخص می‌شود)
    public function sortPlanning($item, $position)
    {
        $this->persistSort($item, $position, 'planning');
    }

    public function sortDoing($item, $position)
    {
        $this->persistSort($item, $position, 'doing');
    }

    public function sortDone($item, $position)
    {
        $this->persistSort($item, $position, 'done');
    }

    private function persistSort($item, $position, string $toStatus): void
    {
        $task = Task::query()->findOrFail($item);
        $fromStatus = $task->status;

        // 1) آیتم را به ستون مقصد و جایگاه جدید منتقل کن
        $task->update([
            'status' => $toStatus,
            'order'  => (int) $position,
        ]);

        // 2) ری-ایندکس کردن order در ستون مقصد (بدون تداخل)
        $this->reindexStatus($toStatus);

        // 3) اگر از ستون دیگری آمده، ستون مبدا را هم ری-ایندکس کن
        if ($fromStatus !== $toStatus) {
            $this->reindexStatus($fromStatus);
        }
    }

    private function reindexStatus(string $status): void
    {
        $tasks = Task::query()
            ->where('status', $status)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($tasks as $i => $t) {
            Task::whereKey($t->id)->update(['order' => $i]);
        }
    }

    public function deleteTask($id)
    {
        Task::find($id)?->delete();
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

            $this->reindexStatus($this->status);
        } else {
            $order = Task::where('status', $this->status)->max('order');
            $order = is_null($order) ? 0 : ($order + 1);

            Task::create([
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status,
                'priority' => $this->priority,
                'due_at' => $this->due_at,
                'created_by' => auth()->id(),
                'order' => $order,
            ]);

            $this->reindexStatus($this->status);
        }

        $this->dispatch('modal-close', id: $this->editingTask ? 'edit-task' : 'create-task');
        $this->reset(['title', 'description', 'status', 'priority', 'due_at', 'editingTask']);
    }

    #[On('panels.workspace.dashboard.index.render')]
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.dashboard.index');
    }
}
