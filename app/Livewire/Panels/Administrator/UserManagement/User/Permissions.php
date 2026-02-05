<?php

namespace App\Livewire\Panel\Administrator\UserManagement\User;

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class Permissions extends Component
{
    use WithPagination;

    public User $user;

    public $search;

    #[On('panel.administrator.user-management.user.permissions.assign-data')]
    public function assignData($id): void
    {
        $this->user = User::findOrFail($id);
        Flux::modal('panel.administrator.user-management.user.permissions.modal')->show();
    }

    public function assign(Permission $permission)
    {
        $this->authorize('administrator_user_management_permissions');

        if (! isset($this->user)) {
            return;
        }
        $this->user->givePermissionTo($permission->name);
        $this->dispatch('panel.administrator.user-management.user.permissions');
    }

    public function delete(Permission $permission): void
    {
        $this->authorize('administrator_user_management_permissions');

        if (! isset($this->user)) {
            return;
        }
        $this->user->revokePermissionTo($permission->name);
        $this->dispatch('panel.administrator.user-management.user.permissions');
    }

    #[On('panel.administrator.user-management.user.permissions.render')]
    public function render()
    {
        $this->authorize('administrator_user_management_permissions');
        if ($this->search != '') {
            $permissions = Permission::where('name', 'like', '%'.$this->search.'%')->paginate();
        } else {
            $permissions = Permission::paginate();
        }

        return view('livewire.panel.administrator.user-management.user.permissions', compact('permissions'));
    }
}
