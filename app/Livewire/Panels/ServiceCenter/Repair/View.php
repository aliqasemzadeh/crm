<?php

namespace App\Livewire\Panels\ServiceCenter\Repair;

use App\Models\ServiceCenter\Repair;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public ?Repair $repair = null;

    #[On('panels.service-center.repair.view.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('service_center_repair_view');

        $this->repair = Repair::with('media')->findOrFail($id);
        $this->repair->ensureShortUrl();

        Flux::modal('panels.service-center.repair.view.modal')->show();
        Flux::toast(__('app.repair_view_loaded'));
    }

    public function render()
    {
        return view('livewire.panels.service-center.repair.view');
    }
}
