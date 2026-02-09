<?php

namespace App\Livewire\Panels\Workspace\Review\User;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        $users = User::query()
            ->whereHas('tasks')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->withCount(['tasks' => function ($query) {
                $query->where('approval_status', '!=', 'approved');
            }])
            ->paginate(12);

        return view('livewire.panels.workspace.review.user.index', [
            'users' => $users
        ]);
    }
}
