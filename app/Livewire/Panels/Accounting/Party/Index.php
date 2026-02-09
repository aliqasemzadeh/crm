<?php

namespace App\Livewire\Panels\Accounting\Party;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }
    #[Computed]
    public function customers()
    {
        return Party::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Name', 'like', '%' . $this->search . '%')
                        ->orWhere('LastName', 'like', '%' . $this->search . '%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(10);
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.party.index');
    }
}
