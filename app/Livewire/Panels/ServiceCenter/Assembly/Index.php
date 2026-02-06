<?php

namespace App\Livewire\Panels\ServiceCenter\Assembly;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('service_center_assembly_index');
    }

    #[Layout('layouts.panels.service-center')]
    public function render()
    {
        return view('livewire.panels.service-center.assembly.index');
    }
}
