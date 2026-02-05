<?php

namespace App\Livewire\Panel\ServiceCenter\Repair;

use App\Enums\StatusEnum;
use App\Models\ServiceCenter\Repair;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'new';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('service_center_repair_index');
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function stats(): Collection
    {
        $counts = Repair::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $allCount = $counts->sum();

        $stats = collect(StatusEnum::cases())->map(fn (StatusEnum $status) => [
            'label' => $status->label(),
            'value' => $counts->get($status->value, 0),
            'color' => $status->color(),
            'status' => $status->value,
        ]);

        $stats->prepend([
            'label' => __('app.all'),
            'value' => $allCount,
            'color' => 'zinc',
            'status' => '',
        ]);

        return $stats;
    }

    #[Computed]
    public function repairs()
    {
        return Repair::query()
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', $search)
                        ->orWhere('owner_name', 'like', $search)
                        ->orWhere('owner_mobile', 'like', $search)
                        ->orWhere('status', 'like', $search)
                        ->orWhere('device_type', 'like', $search)
                        ->orWhere('owner_organization', 'like', $search)
                        ->orWhere('device_serial_number', 'like', $search);
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->tap(function ($query) {
                if ($this->sortBy !== '') {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(10);
    }

    #[Computed]
    public function statusOptions()
    {
        return StatusEnum::options();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[Layout('layouts.panels.service-center')]
    #[On('panel.service-center.repair.index.render')]
    public function render()
    {
        return view('livewire.panel.service-center.repair.index');
    }
}
