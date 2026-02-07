<?php

namespace App\Livewire\Panels\ServiceCenter\Repair;

use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public Repair $repair;

    public int $id;

    #[On('panels.service-center.repair.view.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_view');

        $this->repair = Repair::findOrFail($id);
        $this->id = $this->repair->id;

        Flux::modal('panels.service-center.repair.view.modal')->show();
    }
    public function render()
    {
        return view('livewire.panels.service-center.repair.view');
    }
}
