<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Role;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Permissions extends Component
{
    use WithPagination;

    public Role $role;

    public $search;

    public function mount($id = 1)
    {
        $this->role = Role::findById($id);
    }

    #[On('panel.administrator.user-management.role.permissions.assign-data')]
    public function assignData(int $id): void
    {
        $this->role = Role::findById($id);
        Flux::modal('panel.administrator.user-management.role.permissions.modal')->show();
    }

    public function assign(Permission $permission)
    {
        $this->authorize('administrator_user_management_role_permissions');

        $this->role->givePermissionTo($permission->name);
        $this->dispatch('panel.administrator.user-management.role.permissions');
    }

    public function delete(Permission $permission): void
    {
        $this->authorize('administrator_user_management_role_permissions');

        $this->role->revokePermissionTo($permission->name);
        $this->dispatch('panel.administrator.user-management.role.permissions');
    }

    #[On('panel.administrator.user-management.role.permissions.render')]
    public function render()
    {
        $this->authorize('administrator_user_management_role_permissions');
        if ($this->search != '') {
            $permissions = Permission::where('name', 'like', '%'.$this->search.'%')->paginate();
        } else {
            $permissions = Permission::paginate();
        }

        return view('livewire.panel.administrator.user-management.role.permissions', compact('permissions'));
    }
}
