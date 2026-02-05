<?php

namespace App\Livewire\Panel\ServiceCenter\Repair;

use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public Repair $repair;

    public int $id;

    #[On('panel.service-center.repair.view.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_view');

        $this->repair = Repair::findOrFail($id);
        $this->id = $this->repair->id;

        Flux::modal('panel.service-center.repair.view.modal')->show();
    }
    public function render()
    {
        return view('livewire.panel.service-center.repair.view');
    }
}
