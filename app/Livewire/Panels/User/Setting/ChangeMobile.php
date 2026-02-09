<?php

namespace App\Livewire\Panels\User\Setting;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ChangeMobile extends Component
{
    public string $current_password = '';
    public string $new_mobile = '';

    public function updateMobile()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_mobile' => [
                'required',
                'string',
                'ir_mobile',
                'max:255',
                Rule::unique('users', 'mobile')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update([
            'mobile' => $this->new_mobile,
        ]);

        $this->reset(['current_password', 'new_mobile']);

        Flux::toast(__('app.mobile_updated_successfully'));
    }

    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.setting.change-mobile');
    }
}
