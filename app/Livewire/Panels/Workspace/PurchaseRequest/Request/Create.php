<?php

namespace App\Livewire\Panels\Workspace\Request;

use App\Models\Workspace\PurchaseRequest;
use Flux\Flux;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';
    public int $quantity = 1;
    public string $description = '';
    public string $price = '';
    public string $supplier = '';

    public function create()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['user_id'] = auth()->id();

        PurchaseRequest::create($validated);

        $this->reset(['name', 'quantity', 'description', 'price', 'supplier']);

        $this->dispatch('panels.workspace.purchase-request.index.render');
        Flux::modal('panels.workspace.purchase-request.create.modal')->close();
        Flux::toast(__('app.purchase_request_created_successfully'));
    }

    public function render()
    {
        return view('livewire.panels.workspace.purchase-request.create');
    }
}
