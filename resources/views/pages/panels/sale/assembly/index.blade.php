<?php

use App\Enums\AssemblyOrderStatusEnum;
use App\Models\ServiceCenter\AssemblyOrder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.panels.sale')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('sales_assembly_index');
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function stats(): Collection
    {
        $counts = AssemblyOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = collect(AssemblyOrderStatusEnum::cases())->map(fn (AssemblyOrderStatusEnum $status) => [
            'label' => $status->label(),
            'value' => $counts->get($status->value, 0),
            'color' => $status->color(),
            'status' => $status->value,
        ]);

        $stats->prepend([
            'label' => __('app.all'),
            'value' => $counts->sum(),
            'color' => 'zinc',
            'status' => '',
        ]);

        return $stats;
    }

    #[Computed]
    public function orders()
    {
        return AssemblyOrder::query()
            ->with(['orderedBy'])
            ->withCount([
                'assemblies as ready_assemblies_count' => function ($query) {
                    $query->whereIn('status', ['ready', 'delivered']);
                },
            ])
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', $search)
                        ->orWhere('invoice_number', 'like', $search)
                        ->orWhere('customer_name', 'like', $search);
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return AssemblyOrderStatusEnum::options();
    }

    #[On('panels.sale.assembly.index.render')]
    public function refreshList(): void
    {
        unset($this->orders, $this->stats);
    }
};
?>

<x-slot name="title">
    {{ __('app.assembly_orders') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.assembly_orders') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.assembly_orders_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    @can('sales_assembly_convert')
        <livewire:panels.sale.assembly.convert />
    @endcan
    @can('sales_assembly_view')
        <livewire:panels.sale.assembly.view />
    @endcan

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($this->stats as $stat)
            <div
                wire:click="$set('statusFilter', '{{ $stat['status'] }}')"
                class="relative cursor-pointer rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800 shadow-sm hover:border-{{ $stat['color'] }}-500 transition-colors {{ $statusFilter === $stat['status'] ? 'ring-2 ring-'.$stat['color'].'-500' : '' }}"
            >
                <flux:subheading size="sm" class="uppercase tracking-wider">{{ $stat['label'] }}</flux:subheading>
                <div class="flex items-end justify-between mt-2">
                    <flux:heading size="xl" class="leading-none">{{ $stat['value'] }}</flux:heading>
                    <div class="p-2 rounded-lg bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30">
                        <flux:icon icon="pc-case" variant="micro" class="text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <flux:table :paginate="$this->orders">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="7" class="bg-white dark:bg-zinc-900">
                <div class="flex gap-2 w-full">
                    <div class="flex-1">
                        <flux:input size="sm" placeholder="{{ __('app.search_placeholder') }}" wire:model.live="search" />
                    </div>
                    <div class="w-48">
                        <flux:select size="sm" wire:model.live="statusFilter" placeholder="{{ __('app.filter_status') }}">
                            <option value="">{{ __('app.all_statuses') }}</option>
                            @foreach ($this->statusOptions as $status)
                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    @if ($statusFilter)
                        <flux:button size="sm" variant="ghost" wire:click="clearStatusFilter">{{ __('app.clear') }}</flux:button>
                    @endif
                </div>
            </flux:table.column>
        </flux:table.columns>

        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column>{{ __('app.assembly_order_code') }}</flux:table.column>
            <flux:table.column>{{ __('app.invoice_number') }}</flux:table.column>
            <flux:table.column>{{ __('app.customer') }}</flux:table.column>
            <flux:table.column>{{ __('app.assembly_quantity') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->orders as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell class="whitespace-nowrap font-medium">{{ $order->code }}</flux:table.cell>
                    <flux:table.cell>{{ $order->invoice_number }}</flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $order->customer_name ?: '-' }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ __('app.ordered_by') }}:
                            {{ $order->orderedBy?->name ?? '-' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ __('app.assembly_ready_of_total', ['ready' => $order->ready_assemblies_count, 'total' => $order->quantity]) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="solid" color="{{ $order->status_enum->color() }}">
                            {{ $order->status_enum->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ \Morilog\Jalali\Jalalian::fromCarbon($order->created_at)->format('Y/m/d H:i') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @can('sales_assembly_view')
                            <flux:tooltip content="{{ __('app.view') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="sky"
                                    icon="eye"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.sale.assembly.view.assign-data', { id: {{ $order->id }} })"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('app.no_assembly_orders') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
