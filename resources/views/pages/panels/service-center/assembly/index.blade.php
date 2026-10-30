<?php

use App\Enums\AssemblyStatusEnum;
use App\Models\ServiceCenter\Assembly;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.panels.service-center')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'pending';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorize('service_center_assembly_index');
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
        $counts = Assembly::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = collect(AssemblyStatusEnum::cases())->map(fn (AssemblyStatusEnum $status) => [
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
    public function assemblies()
    {
        return Assembly::query()
            ->with(['order.orderedBy', 'assembledBy'])
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($q) use ($search) {
                    $q->where('system_serial', 'like', $search)
                        ->orWhere('unit_number', 'like', $search)
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('code', 'like', $search)
                                ->orWhere('invoice_number', 'like', $search)
                                ->orWhere('customer_name', 'like', $search);
                        });
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    #[Computed]
    public function statusOptions(): array
    {
        return AssemblyStatusEnum::options();
    }

    #[On('panels.service-center.assembly.index.render')]
    public function refreshList(): void
    {
        unset($this->assemblies, $this->stats);
    }
};
?>

<x-slot name="title">
    {{ __('app.assemblies') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.assemblies') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('app.assemblies_description') }}</flux:subheading>
        </div>
        <flux:separator variant="subtle" />
    </div>

    @can('service_center_assembly_work')
        <livewire:panels.service-center.assembly.work />
    @endcan
    @can('service_center_assembly_logs')
        <livewire:panels.service-center.assembly.logs />
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

    <flux:table :paginate="$this->assemblies">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="8" class="bg-white dark:bg-zinc-900">
                <div class="flex gap-2 w-full">
                    <div class="flex-1">
                        <flux:input size="sm" placeholder="{{ __('app.search_placeholder') }}" wire:model.live="search" />
                    </div>
                    <div class="w-48">
                        <flux:select size="sm" wire:model.live="statusFilter">
                            <option value="">{{ __('app.all_statuses') }}</option>
                            @foreach ($this->statusOptions as $status)
                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    @if ($statusFilter !== '')
                        <flux:button size="sm" variant="ghost" wire:click="clearStatusFilter">{{ __('app.clear') }}</flux:button>
                    @endif
                </div>
            </flux:table.column>
        </flux:table.columns>

        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column>{{ __('app.assembly_order_code') }}</flux:table.column>
            <flux:table.column>{{ __('app.assembly_unit_number') }}</flux:table.column>
            <flux:table.column>{{ __('app.customer') }}</flux:table.column>
            <flux:table.column>{{ __('app.assembly_system_serial') }}</flux:table.column>
            <flux:table.column>{{ __('app.assembled_by') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->assemblies as $assembly)
                @php
                    $statusEnum = $assembly->status instanceof \App\Enums\AssemblyStatusEnum
                        ? $assembly->status
                        : \App\Enums\AssemblyStatusEnum::tryFromSafe((string) $assembly->status);
                    $duration = $assembly->durationMinutes();
                @endphp
                <flux:table.row :key="$assembly->id">
                    <flux:table.cell class="whitespace-nowrap font-medium">
                        {{ $assembly->order?->code }}
                        <div class="text-xs text-zinc-500">{{ __('app.invoice_number') }}: {{ $assembly->order?->invoice_number }}</div>
                    </flux:table.cell>
                    <flux:table.cell>#{{ $assembly->unit_number }}</flux:table.cell>
                    <flux:table.cell>{{ $assembly->order?->customer_name ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $assembly->system_serial ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $assembly->assembledBy?->name ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="solid" color="{{ $statusEnum->color() }}">{{ $statusEnum->label() }}</flux:badge>
                        @if ($duration !== null)
                            <div class="mt-1 text-xs text-zinc-500">
                                {{ __('app.assembly_duration_minutes', ['minutes' => $duration]) }}
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        {{ \Morilog\Jalali\Jalalian::fromCarbon($assembly->created_at)->format('Y/m/d H:i') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @can('service_center_assembly_work')
                                <flux:tooltip content="{{ __('app.assembly_work') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="orange"
                                        icon="wrench"
                                        icon:variant="outline"
                                        wire:click="$dispatch('panels.service-center.assembly.work.assign-data', { id: {{ $assembly->id }} })"
                                    />
                                </flux:tooltip>
                            @endcan
                            @can('service_center_assembly_logs')
                                <flux:tooltip content="{{ __('app.assembly_logs') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="lime"
                                        icon="scroll-text"
                                        icon:variant="outline"
                                        wire:click="$dispatch('panels.service-center.assembly.logs.assign-data', { id: {{ $assembly->id }} })"
                                    />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">{{ __('app.no_assemblies') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
