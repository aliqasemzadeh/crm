<?php

namespace App\Livewire\Panels\Workspace\Request;

use App\Models\Workspace\PurchaseRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $listeners = [
        'panels.workspace.purchase-request.index.render' => 'render'
    ];

    #[Layout('layouts.panels.workspace')]
    public function render()
    {
        $query = PurchaseRequest::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });

        // If not admin, only show user's own requests
        if (!auth()->user()->hasRole('administrator')) {
            $query->where('user_id', auth()->id());
        }

        return view('livewire.panels.workspace.purchase-request.index', [
            'requests' => $query->latest()->paginate(10)
        ]);
    }
}
