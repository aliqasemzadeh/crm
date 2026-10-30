<?php

namespace App\Livewire\Panels\Sale\Assembly;

use App\Models\ServiceCenter\AssemblyOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class View extends Component
{
    public ?AssemblyOrder $order = null;

    #[On('panels.sale.assembly.view.assign-data')]
    public function assignData(int $id): void
    {
        $this->authorize('sales_assembly_view');

        $this->order = AssemblyOrder::query()
            ->with([
                'orderedBy',
                'parts',
                'assemblies.assembledBy',
                'assemblies.parts',
            ])
            ->findOrFail($id);

        Flux::modal('panels.sale.assembly.view.modal')->show();
    }

    #[Computed]
    public function readyCount(): int
    {
        if (! $this->order) {
            return 0;
        }

        return $this->order->readyCount();
    }

    public function render()
    {
        return view('livewire.panels.sale.assembly.view');
    }
}
