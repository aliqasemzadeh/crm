<?php

namespace App\Livewire\Panels\Accounting\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.dashboard.index');
    }
}
