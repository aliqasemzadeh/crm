<?php

namespace App\Livewire\Panels\Workspace\Task;

use App\Models\User;
use App\Models\Workspace\Task;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Assigns extends Component
{
    public Task $task;
    public $search = '';

    public function mount(Task $task)
    {
        $this->task = $task;
    }

    public function assign($userId, $role = 'assignee')
    {
        $this->task->users()->syncWithoutDetaching([
            $userId => [
                'role' => $role,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]
        ]);

        $user = User::findOrFail($userId);

        \App\Jobs\Notification\SendSmsMessageJob::dispatch($user->mobile, "فعالیت:" . $this->task->title . " در میزکار شما قرارگرفت.");

        Flux::toast(
            text: __('app.task.notifications.assigned'),
            variant: 'success',
        );
    }

    public function delete($userId, $role)
    {
        $this->task->users()
            ->wherePivot('user_id', $userId)
            ->wherePivot('role', $role)
            ->detach($userId);

        Flux::toast(
            text: __('app.task.notifications.unassigned'),
            variant: 'success',
        );
    }

    #[Layout('layouts.panels.workspace')]
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

        return view('livewire.panels.workspace.task.assigns', [
            'users' => $users,
            'assignedUsers' => $this->task->users()->withPivot('role')->get(),
        ]);
    }
}
