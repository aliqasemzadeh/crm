<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Permission;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class Edit extends Component
{
    public Permission $permission;

    public string $name = '';

    public string $guard_name = 'web';

    public function mount($id = 1)
    {
        $this->permission = Permission::findById($id);
        $this->name = $this->permission->name;
        $this->guard_name = $this->permission->guard_name;
    }

    #[On('panel.administrator.user-management.permission.edit.assign-data')]
    public function assignData(int $id): void
    {
        $this->permission = Permission::findById($id);
        $this->name = $this->permission->name;
        $this->guard_name = $this->permission->guard_name;
        Flux::modal('panel.administrator.user-management.permission.edit.modal')->show();
    }

    public function edit()
    {
        $this->authorize('administrator_user_management_permission_edit');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($this->permission->id)],
            'guard_name' => ['required', 'string', 'max:255', 'in:web'],
        ]);

        $this->permission->update($validated);

        $this->dispatch('pg:eventRefresh-administrator.user-management.permission.index');
        Flux::modal('panel.administrator.user-management.permission.edit.modal')->close();
    }

    public function render()
    {
        return view('livewire.panel.administrator.user-management.permission.edit');
    }
}
