<?php

namespace App\Livewire\Panels\Accounting\Item;

use App\Models\Sepidar\INV\Item;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortBy = 'CreationDate';
    public $sortDirection = 'desc';

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->with(['grouping', 'image', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('Code', 'like', '%' . $this->search . '%')
                        ->orWhere('Title', 'like', '%' . $this->search . '%');
                });
            })
            ->tap(fn($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->paginate(15);
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.item.index');
    }
}
