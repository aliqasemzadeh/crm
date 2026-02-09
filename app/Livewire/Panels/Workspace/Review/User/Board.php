<?php

namespace App\Livewire\Panels\Workspace\Review\User;

use App\Models\User;
use App\Models\Workspace\Task;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Board extends Component
{
    public User $user;
    public $statuses = ['planning', 'doing', 'done'];

    public function mount(User $user)
    {
        $this->user = $user;
    }

    #[Computed]
    public function tasks()
    {
        return $this->user
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

    private function moveTask(int $taskId, int $newPosition, string $toStatus): void
    {
        $userId = $this->user->id;

        DB::transaction(function () use ($taskId, $newPosition, $toStatus, $userId) {
            $task = Task::query()
                ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                ->lockForUpdate()
                ->findOrFail($taskId);

            $fromStatus = $task->status;
            $fromOrder  = (int) $task->order;

            $scopeStatusForUser = function (string $status) use ($userId) {
                return Task::query()
                    ->where('status', $status)
                    ->whereHas('users', fn ($q) => $q->where('users.id', $userId));
            };

            if ($fromStatus === $toStatus) {
                if ($newPosition === $fromOrder) return;

                if ($newPosition > $fromOrder) {
                    $scopeStatusForUser($toStatus)
                        ->where('order', '>', $fromOrder)
                        ->where('order', '<=', $newPosition)
                        ->update(['order' => DB::raw('`order` - 1')]);
                } else {
                    $scopeStatusForUser($toStatus)
                        ->where('order', '>=', $newPosition)
                        ->where('order', '<', $fromOrder)
                        ->update(['order' => DB::raw('`order` + 1')]);
                }

                $task->update(['order' => $newPosition]);
                return;
            }

            $scopeStatusForUser($toStatus)
                ->where('order', '>=', $newPosition)
                ->update(['order' => DB::raw('`order` + 1')]);

            $task->update(['status' => $toStatus, 'order' => $newPosition]);

            $scopeStatusForUser($fromStatus)
                ->where('order', '>', $fromOrder)
                ->update(['order' => DB::raw('`order` - 1')]);
        });
    }

    public function deleteTask($id): void
    {
        Task::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $this->user->id))
            ->whereKey($id)
            ->first()?->delete();
    }

    #[On('panels.workspace.review.user.board.render')]
    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        return view('livewire.panels.workspace.review.user.board');
    }
}
