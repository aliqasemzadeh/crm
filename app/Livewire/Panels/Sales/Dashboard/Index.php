<?php

namespace App\Livewire\Panels\Sales\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.sales')]
    public function render()
    {
        return view('livewire.panels.sales.dashboard.index');
    }
}
