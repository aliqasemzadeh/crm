<?php

namespace App\Livewire\Panels\Sales\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('sales_dashboard_index');
    }

    #[Layout('layouts.panels.sale')]
    public function render()
    {
        return view('livewire.panels.sales.dashboard.index');
    }
}
