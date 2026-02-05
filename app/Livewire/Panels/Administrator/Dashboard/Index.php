<?php

namespace App\Livewire\Panels\Administrator\Dashboard;

use App\Models\Sepidar\INV\InventoryReceiptItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.panels.administrator')]
    public function render()
    {
        $this->authorize('administrator_dashboard_index');
        return view('livewire.panels.administrator.dashboard.index');
    }
}
