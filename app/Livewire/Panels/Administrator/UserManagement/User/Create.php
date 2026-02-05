<?php

namespace App\Livewire\Panel\Administrator\UserManagement\User;

use App\Models\User;
use Flux\Flux;
use Livewire\Component;

class Create extends Component
{
    public string $mobile = '';

    public function create()
    {
        $this->authorize('administrator_user_management_create');

        $validated = $this->validate([
            'mobile' => ['required', 'string', 'lowercase', 'ir_mobile', 'max:255', 'unique:'.User::class],
        ]);

        User::firstOrCreate($validated);

        $this->dispatch('panel.administrator.user-management.user.index.render');
        Flux::modal('panel.administrator.user-management.user.create.modal')->close();
    }

    public function render()
    {
        return view('livewire.panel.administrator.user-management.user.create');
    }
}
