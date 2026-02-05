<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Role;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Edit extends Component
{
    public Role $role;

    public string $name = '';

    public string $guard_name = 'web';

    public function mount($id = 1)
    {
        $this->role = Role::findById($id);
        $this->name = $this->role->name;
        $this->guard_name = $this->role->guard_name;
    }

    #[On('panel.administrator.user-management.role.edit.assign-data')]
    public function assignData(int $id): void
    {
        $this->role = Role::findById($id);
        $this->name = $this->role->name;
        $this->guard_name = $this->role->guard_name;
        Flux::modal('panel.administrator.user-management.role.edit.modal')->show();
    }

    public function edit()
    {
        $this->authorize('administrator_user_management_role_edit');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->role->id)],
            'guard_name' => ['required', 'string', 'max:255', 'in:web'],
        ]);

        $this->role->update($validated);

        $this->dispatch('pg:eventRefresh-administrator.user-management.role.index');
        Flux::modal('panel.administrator.user-management.role.edit.modal')->close();

    }

    public function render()
    {
        return view('livewire.panel.administrator.user-management.role.edit');
    }
}
