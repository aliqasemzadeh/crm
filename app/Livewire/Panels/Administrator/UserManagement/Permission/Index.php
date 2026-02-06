<?php

namespace App\Livewire\Panels\Administrator\UserManagement\Permission;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class Index extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public string $search = '';
    public int $page = 1;

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function permissions()
    {
        return Permission::query()
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('guard_name', 'like', $search);
                });
            })
            ->tap(fn($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(10);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        $this->authorize('administrator_user_management_permission_index');
        return view('livewire.panels.administrator.user-management.permission.index');
    }
}
