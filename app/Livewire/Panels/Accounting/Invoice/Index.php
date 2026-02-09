<?php

namespace App\Livewire\Panels\Accounting\Invoice;

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $sortBy = 'Date';

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
    public function invoices()
    {
        return Invoice::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('CustomerRealName', 'like', '%' . $this->search . '%')
                        ->orWhere('Number', 'like', '%' . $this->search . '%');
                });
            })
            ->tap(function ($query) {
                if ($this->sortBy) {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(50);
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.invoice.index');
    }
}
