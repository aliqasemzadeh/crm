<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.sale')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('sales_dashboard_index');
    }
};
?>

<x-slot name="title">
    {{ __('app.sales') }} — {{ __('app.dashboard') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.sales') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('app.sales_dashboard_description') }}</flux:subheading>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        @can('sales_invoice_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.invoices') }}</flux:heading>
                <flux:text>{{ __('app.invoices_description') }}</flux:text>
                <flux:button variant="primary" color="orange" href="{{ route('panels.sale.invoice.index') }}" wire:navigate class="w-full">
                    {{ __('app.invoices') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_item_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.items') }}</flux:heading>
                <flux:text>{{ __('app.sales_items_description') }}</flux:text>
                <flux:button variant="primary" color="teal" href="{{ route('panels.sale.item.index') }}" wire:navigate class="w-full">
                    {{ __('app.items') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_item_price_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.price_notes') }}</flux:heading>
                <flux:text>{{ __('app.sales_item_price_description') }}</flux:text>
                <flux:button variant="primary" color="sky" href="{{ route('panels.sale.item-price.index') }}" wire:navigate class="w-full">
                    {{ __('app.price_notes') }}
                </flux:button>
            </flux:card>
        @endcan

        @can('sales_party_index')
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('app.customers') }}</flux:heading>
                <flux:text>{{ __('app.sales_customers_description') }}</flux:text>
                <flux:button variant="primary" color="violet" href="{{ route('panels.sale.party.index') }}" wire:navigate class="w-full">
                    {{ __('app.customers') }}
                </flux:button>
            </flux:card>
        @endcan
    </div>
</div>
