<?php

namespace App\Livewire\Panel\Administrator\UserManagement\User;

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Roles extends Component
{
    use WithPagination;

    public User $user;

    public $search;

    #[On('panel.administrator.user-management.user.roles.assign-data')]
    public function assignData($id): void
    {
        $this->user = User::findOrFail($id);
        Flux::modal('panel.administrator.user-management.user.roles.modal')->show();
    }

    public function assign(Role $role)
    {
        $this->authorize('administrator_user_management_roles');

        if (! isset($this->user)) {
            return;
        }
        $this->user->assignRole($role->name);
        $this->dispatch('panel.administrator.user-management.user.roles');
    }

    public function delete(Role $role): void
    {
        $this->authorize('administrator_user_management_roles');

        if (! isset($this->user)) {
            return;
        }
        $this->user->removeRole($role->name);
        $this->dispatch('panel.administrator.user-management.user.roles');
    }

    #[On('panel.administrator.user-management.user.roles.render')]
    public function render()
    {
        $this->authorize('administrator_user_management_roles');
        if ($this->search != '') {
            $roles = Role::where('name', 'like', '%'.$this->search.'%')->paginate();
        } else {
            $roles = Role::paginate();
        }

        return view('livewire.panel.administrator.user-management.user.roles', compact('roles'));
    }
}
