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
        // فقط task های همین کاربر که تایید نهایی نشده‌اند
        return auth()
            ->user()
            ->tasks()
            ->with('users')
            ->where('approval_status', '!=', 'approved')
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

        try {
            DB::transaction(function () use ($taskId, $newPosition, $toStatus, $userId) {
                // امنیت: فقط taskهایی که به این کاربر وصل‌اند قابل جابجایی باشند
                $task = Task::query()
                    ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                    ->lockForUpdate()
                    ->findOrFail($taskId);

                $fromStatus = $task->status;
                $fromOrder  = (int) $task->order;

                // Scopes: فقط taskهای همین کاربر در همان status که تایید نشده‌اند
                $scopeStatusForUser = function (string $status) use ($userId) {
                    return Task::query()
                        ->where('status', $status)
                        ->where('approval_status', '!=', 'approved')
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

                    $task->update([
                        'order' => $newPosition,
                    ]);

                    return;
                }

                // بین ستون‌ها
                // 1) مقصد جا باز کند: order >= newPosition => +1
                $scopeStatusForUser($toStatus)
                    ->where('order', '>=', $newPosition)
                    ->update(['order' => DB::raw('`order` + 1')]);

                // 2) task منتقل شود
                $task->update([
                    'status' => $toStatus,
                    'order'  => $newPosition,
                ]);

                // 3) مبدا جمع شود: order > fromOrder => -1
                $scopeStatusForUser($fromStatus)
                    ->where('order', '>', $fromOrder)
                    ->update(['order' => DB::raw('`order` - 1')]);
            });
        } catch (\Exception $e) {
            \Flux\Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function deleteTask($id): void
    {
        // فقط taskهای همین کاربر که done نیستند
        Task::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))
            ->whereKey($id)
            ->where('status', '!=', 'done')
            ->first()?->delete();
    }

    #[On('panels.workspace.dashboard.index.render')]
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.dashboard.index');
    }
}
