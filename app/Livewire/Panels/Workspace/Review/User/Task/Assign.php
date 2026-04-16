<?php

namespace App\Livewire\Panels\Workspace\Review\User\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class Assign extends Component
{
    public ?Task $task = null;
    public $search = '';

    #[On('panels.workspace.review.user.task.assign.assign-data')]
    public function assignData($id)
    {
        $this->task = Task::findOrFail($id);

        if ($this->task->approval_status === 'approved') {
            Flux::toast(__('app.task.notifications.cannot_edit_approved'), variant: 'danger');
            return;
        }

        $this->search = '';
        Flux::modal('panels.workspace.review.user.task.assign.modal')->show();
    }

    public function assign($userId, $role = 'assignee')
    {
        if (!$this->task || $this->task->approval_status === 'approved') {
            return;
        }

        $this->task->users()->syncWithoutDetaching([
            $userId => [
                'role' => $role,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]
        ]);

        $user = User::findOrFail($userId);

        try {
            \App\Jobs\Notification\SendSmsMessageJob::dispatch($user->mobile, "فعالیت:" . $this->task->title . " در میزکار شما قرارگرفت.");
        } catch (\Exception $e) {
            // Log error or ignore
        }

        Flux::toast(
            text: __('app.task.notifications.assigned'),
            variant: 'success',
        );

        $this->dispatch('panels.workspace.review.user.board.render');
    }

    public function delete($userId, $role)
    {
        if (!$this->task || $this->task->approval_status === 'approved') {
            return;
        }

        $this->task->users()
            ->wherePivot('user_id', $userId)
            ->wherePivot('role', $role)
            ->detach($userId);

        Flux::toast(
            text: __('app.task.notifications.unassigned'),
            variant: 'success',
        );

        $this->dispatch('panels.workspace.review.user.board.render');
    }

    public function render()
    {
        $users = [];
        if (strlen($this->search) >= 2) {
            $users = User::where('first_name', 'like', '%' . $this->search . '%')
                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                ->orWhere('mobile', 'like', '%' . $this->search . '%')
                ->limit(10)
                ->get();
        }

        return view('livewire.panels.workspace.review.user.task.assign', [
            'users' => $users,
            'assignedUsers' => $this->task ? $this->task->users()->withPivot('role')->get() : collect([]),
        ]);
    }
}
