<?php

namespace App\Livewire\Panel\ServiceCenter\Repair;

use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Problem extends Component
{
    public Repair $repair;

    public int $id;

    #[On('panel.service-center.repair.problem.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_view');

        $this->repair = Repair::findOrFail($id);
        $this->id = $this->repair->id;

        Flux::modal('panel.service-center.repair.problem.modal')->show();
    }

    public function render(): View
    {
        return view('livewire.panel.service-center.repair.problem');
    }
}
