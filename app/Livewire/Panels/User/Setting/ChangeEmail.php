<?php

namespace App\Livewire\Panels\User\Setting;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ChangeEmail extends Component
{
    public string $current_password = '';
    public string $new_email = '';

    public function updateEmail()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update([
            'email' => $this->new_email,
        ]);

        $this->reset(['current_password', 'new_email']);

        Flux::toast(__('app.email_updated_successfully'));
    }

    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.setting.change-email');
    }
}
