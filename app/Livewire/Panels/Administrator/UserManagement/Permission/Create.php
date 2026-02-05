<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Permission;

use Flux\Flux;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class Create extends Component
{
    public string $name = '';
    public string $guard_name = 'web';

    public function create()
    {
        $this->authorize('administrator_user_management_permission_create');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:' . Permission::class],
            'guard_name' => ['required', 'string', 'max:255', 'in:web'],
        ]);

        Permission::create($validated);

        $this->dispatch('pg:eventRefresh-administrator.user-management.permission.index');
        Flux::modal('panel.administrator.user-management.permission.create.modal')->close();

    }
    public function render()
    {
        return view('livewire.panel.administrator.user-management.permission.create');
    }
}
