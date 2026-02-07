<?php

namespace App\Livewire\Panels\Workspace\Dashboard;

use App\Models\Workspace\Task;
use Illuminate\Support\Facades\DB;
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

    #[Computed]
    public function tasks()
    {
        // فقط task های همین کاربر
        return auth()
            ->user()
            ->tasks()
            ->orderByRaw("
            CASE status
                WHEN 'planning' THEN 1
                WHEN 'doing' THEN 2
                WHEN 'done' THEN 3
                ELSE 99
            END
        ")
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    // ✅ برای هر ستون یک handler
    public function sortPlanning($item, $position): void
    {
        $this->moveTask((int) $item, (int) $position, 'planning');
    }

    public function sortDoing($item, $position): void
    {
        $this->moveTask((int) $item, (int) $position, 'doing');
    }

    public function sortDone($item, $position): void
    {
        $this->moveTask((int) $item, (int) $position, 'done');
    }

    /**
     * مرتب‌سازی صحیح با شیفت دادن بازه‌ها
     * - فقط روی task های همین کاربر
     * - بدون reindex کامل
     */
    private function moveTask(int $taskId, int $newPosition, string $toStatus): void
    {
        $userId = auth()->id();

        DB::transaction(function () use ($taskId, $newPosition, $toStatus, $userId) {
            // امنیت: فقط taskهایی که به این کاربر وصل‌اند قابل جابجایی باشند
            $task = Task::query()
                ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                ->lockForUpdate()
                ->findOrFail($taskId);

            $fromStatus = $task->status;
            $fromOrder  = (int) $task->order;

            // وقتی وارد done شد و هنوز approval ندارد => pending
            $approvalPatch = [];
            if ($toStatus === 'done' && in_array($task->approval_status ?? 'none', ['none', null], true)) {
                $approvalPatch = ['approval_status' => 'pending'];
            }

            // Scopes: فقط taskهای همین کاربر در همان status
            $scopeStatusForUser = function (string $status) use ($userId) {
                return Task::query()
                    ->where('status', $status)
                    ->whereHas('users', fn ($q) => $q->where('users.id', $userId));
            };

            // داخل همان ستون
            if ($fromStatus === $toStatus) {
                if ($newPosition === $fromOrder) {
                    return;
                }

                if ($newPosition > $fromOrder) {
                    // حرکت به پایین: (fromOrder+1 .. newPosition) => order - 1
                    $scopeStatusForUser($toStatus)
                        ->where('order', '>', $fromOrder)
                        ->where('order', '<=', $newPosition)
                        ->update(['order' => DB::raw('`order` - 1')]);
                } else {
                    // حرکت به بالا: (newPosition .. fromOrder-1) => order + 1
                    $scopeStatusForUser($toStatus)
                        ->where('order', '>=', $newPosition)
                        ->where('order', '<', $fromOrder)
                        ->update(['order' => DB::raw('`order` + 1')]);
                }

                $task->update(array_merge([
                    'order' => $newPosition,
                ], $approvalPatch));

                return;
            }

            // بین ستون‌ها
            // 1) مقصد جا باز کند: order >= newPosition => +1
            $scopeStatusForUser($toStatus)
                ->where('order', '>=', $newPosition)
                ->update(['order' => DB::raw('`order` + 1')]);

            // 2) task منتقل شود
            $task->update(array_merge([
                'status' => $toStatus,
                'order'  => $newPosition,
            ], $approvalPatch));

            // 3) مبدا جمع شود: order > fromOrder => -1
            $scopeStatusForUser($fromStatus)
                ->where('order', '>', $fromOrder)
                ->update(['order' => DB::raw('`order` - 1')]);
        });
    }

    public function deleteTask($id): void
    {
        // فقط taskهای همین کاربر
        Task::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))
            ->whereKey($id)
            ->first()?->delete();
    }

    public function openCreateModal($status = 'planning'): void
    {
        $this->reset(['title', 'description', 'due_at', 'editingTask']);
        $this->status = $status;
        $this->priority = 'medium';
        $this->dispatch('modal-show', id: 'create-task');
    }

    public function openEditModal(Task $task): void
    {
        // امنیت: فقط taskهای این کاربر قابل ادیت
        abort_unless(
            $task->users()->where('users.id', auth()->id())->exists(),
            403
        );

        $this->editingTask = $task;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->status = $task->status;
        $this->priority = $task->priority;
        $this->due_at = $task->due_at?->format('Y-m-d');
        $this->dispatch('modal-show', id: 'edit-task');
    }

    public function saveTask(): void
    {
        $this->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:planning,doing,done',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_at' => 'nullable|date',
        ]);

        $userId = auth()->id();

        DB::transaction(function () use ($userId) {
            if ($this->editingTask) {
                // امنیت: فقط taskهای این کاربر
                $task = Task::query()
                    ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                    ->lockForUpdate()
                    ->findOrFail($this->editingTask->id);

                $oldStatus = $task->status;

                $task->update([
                    'title' => $this->title,
                    'description' => $this->description,
                    'status' => $this->status,
                    'priority' => $this->priority,
                    'due_at' => $this->due_at,
                ]);

                // اگر رفت به done و approval ندارد => pending
                if ($task->status === 'done' && in_array($task->approval_status ?? 'none', ['none', null], true)) {
                    $task->update(['approval_status' => 'pending']);
                }

                // اگر status تغییر کرد، به انتهای ستون مقصد ببر (تا ترتیب قاطی نشود)
                if ($oldStatus !== $task->status) {
                    $max = Task::query()
                        ->where('status', $task->status)
                        ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                        ->max('order');

                    $task->update(['order' => is_null($max) ? 0 : ($max + 1)]);
                }

                return;
            }

            $max = Task::query()
                ->where('status', $this->status)
                ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                ->max('order');

            $order = is_null($max) ? 0 : ($max + 1);

            $approval_status = ($this->status === 'done') ? 'pending' : 'none';

            $task = Task::create([
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status,
                'priority' => $this->priority,
                'due_at' => $this->due_at,
                'created_by' => $userId,
                'order' => $order,
                'approval_status' => $approval_status,
                'source' => 'user',
            ]);

            // اگر می‌خواهی task اتومات به سازنده وصل شود:
            $task->users()->syncWithoutDetaching([
                $userId => [
                    'role' => 'assignee',
                    'assigned_at' => now(),
                    'assigned_by' => $userId,
                ],
            ]);
        });

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
