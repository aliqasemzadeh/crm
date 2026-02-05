<?php

namespace App\Livewire\Panel\Administrator\UserManagement\Role;

use Livewire\Component;

class Users extends Component
{
    public \Spatie\Permission\Models\Role $role;
    public function mount($id = 1)
    {
        $this->role = \Spatie\Permission\Models\Role::findById($id);
    }

    public function revoke($id, $name)
    {
        $this->authorize('administrator_user_management_role_users');

        $selected_user = \App\Models\User::findOrFail($id);
        $selected_user->removeRole($name);
    }

    public function render()
    {
        $this->authorize('administrator_user_management_role_users');
        return view('livewire.panel.administrator.user-management.role.users');
    }
}
