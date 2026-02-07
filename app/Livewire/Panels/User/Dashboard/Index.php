<?php

namespace App\Livewire\Panels\User\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.user')]
    public function render()
    {
        return view('livewire.panels.user.dashboard.index');
    }
}
