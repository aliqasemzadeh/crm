<?php

namespace App\Livewire\Panels\Workspace\Request;

use App\Models\Workspace\PurchaseRequest;
use Flux\Flux;
use Livewire\Component;

class View extends Component
{
    public ?PurchaseRequest $request = null;

    protected $listeners = [
        'panels.workspace.purchase-request.view.show' => 'show'
    ];

    public function show($id)
    {
        $this->request = PurchaseRequest::with('user')->findOrFail($id);
        Flux::modal('panels.workspace.purchase-request.view.modal')->show();
    }

    public function render()
    {
        return view('livewire.panels.workspace.purchase-request.view');
    }

    public function handleAction()
    {
        // This might be needed depending on how the approval component interacts
        $this->dispatch('panels.workspace.purchase-request.index.render');
    }
}
