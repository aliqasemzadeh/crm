<?php

namespace App\Livewire\Panels\Administrator\UserManagement\User;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use \Livewire\WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function users()
    {
        return \App\Models\User::query()
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('mobile', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(20);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function impersonate(int $userId)
    {
        $this->authorize('administrator_user_management_edit');

        $user = \App\Models\User::query()->findOrFail($userId);

        if ((int) Auth::id() === (int) $user->id) {
            Flux::toast(__('app.cannot_login_as_self'), variant: 'danger');

            return;
        }

        Auth::login($user);
        request()->session()->regenerate();

        Flux::toast(__('app.login_as_user_success'));

        return $this->redirectRoute('home', navigate: true);
    }

    #[Layout('layouts.panels.administrator')]
    #[On('panels.administrator.user-management.user.index.render')]
    public function render()
    {
        $this->authorize('administrator_user_management_index');

        return view('livewire.panels.administrator.user-management.user.index');
    }
}
