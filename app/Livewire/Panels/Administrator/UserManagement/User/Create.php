<?php

namespace App\Livewire\Panels\Administrator\UserManagement\User;

use App\Models\User;
use Flux\Flux;
use Livewire\Component;

class Create extends Component
{
    public string $mobile = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function create()
    {
        $this->authorize('administrator_user_management_create');

        $validated = $this->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'ir_mobile', 'max:255', 'unique:'.User::class],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        User::create($validated);

        $this->reset(['mobile', 'first_name', 'last_name', 'email', 'password', 'password_confirmation']);

        $this->dispatch('panels.administrator.user-management.user.index.render');
        Flux::modal('panels.administrator.user-management.user.create.modal')->close();
    }

    public function render()
    {
        return view('livewire.panels.administrator.user-management.user.create');
    }
}
