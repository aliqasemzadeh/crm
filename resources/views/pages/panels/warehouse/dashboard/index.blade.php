<?php

use App\Models\Sepidar\INV\Item;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('warehouse_dashboard_index');
    }

    #[Computed]
    public function overview(): array
    {
        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');

        return Cache::remember('warehouse_dashboard_overview_in_stock_'.$fiscalYearRef, now()->addMinutes(10), function () use ($fiscalYearRef) {
            return [
                'total' => Item::query()->whereInStock($fiscalYearRef)->count(),
                'without_image' => Item::query()->whereInStock($fiscalYearRef)->doesntHave('image')->count(),
                'without_irancode' => Item::query()->whereInStock($fiscalYearRef)->where(function ($q) {
                    $q->whereNull('IranCode')->orWhere('IranCode', '=', '');
                })->count(),
            ];
        });
    }
};
?>

<x-slot name="title">
    {{ __('app.warehouse_dashboard_title') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.warehouse_dashboard_title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_dashboard_subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <flux:card class="p-5">
            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.all_items') }}</flux:subheading>
            <flux:heading size="xl" class="mt-2">{{ number_format($this->overview['total']) }}</flux:heading>
        </flux:card>
        <flux:card class="p-5">
            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.without_image') }}</flux:subheading>
            <flux:heading size="xl" class="mt-2 text-amber-600 dark:text-amber-400">{{ number_format($this->overview['without_image']) }}</flux:heading>
        </flux:card>
        <flux:card class="p-5">
            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.without_irancode') }}</flux:subheading>
            <flux:heading size="xl" class="mt-2 text-rose-600 dark:text-rose-400">{{ number_format($this->overview['without_irancode']) }}</flux:heading>
        </flux:card>
    </div>

    @can('warehouse_item_index')
        <div class="flex flex-wrap gap-3">
            <flux:button variant="primary" color="teal" href="{{ route('panels.warehouse.item.index') }}" wire:navigate icon="boxes" icon:variant="outline">
                {{ __('app.warehouse_open_item_list') }}
            </flux:button>
            <flux:button variant="primary" color="sky" href="{{ route('panels.warehouse.history.index') }}" wire:navigate icon="calendar" icon:variant="outline">
                {{ __('app.warehouse_history_nav') }}
            </flux:button>
        </div>
    @endcan
</div>
