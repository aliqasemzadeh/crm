<?php

namespace App\Livewire\Panels\User\Setting;

use Livewire\Attributes\Layout;
use Livewire\Component;

class ChangeEmail extends Component
{
    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.setting.change-email');
    }
}
