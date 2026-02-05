<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Role;

use Flux\Flux;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Create extends Component
{
    public string $name = '';
    public string $guard_name = 'web';

    public function create()
    {
        $this->authorize('administrator_user_management_role_create');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:' . Role::class],
            'guard_name' => ['required', 'string', 'max:255', 'in:web'],
        ]);

        Role::create($validated);

        $this->dispatch('pg:eventRefresh-administrator.user-management.role.index');
        Flux::modal('panel.administrator.user-management.role.create.modal')->close();

    }

    public function render()
    {
        return view('livewire.panel.administrator.user-management.role.create');
    }
}
