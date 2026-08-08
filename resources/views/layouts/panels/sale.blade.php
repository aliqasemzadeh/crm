<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="{{ __('app.direction') }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="panel-shell-sidebar dark:bg-zinc-900 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <flux:sidebar.search placeholder="{{ __('app.search_placeholder') }}" />

    <flux:sidebar.nav>
        @can('sales_dashboard_index')
            <flux:sidebar.item
                icon="layout-dashboard"
                href="{{ route('panels.sale.dashboard.index') }}"
                :current="request()->routeIs('panels.sale.dashboard.*')"
                wire:navigate
            >
                {{ __('app.dashboard') }}
            </flux:sidebar.item>
        @endcan

        @can('sales_invoice_index')
            <flux:sidebar.item
                icon="file-text"
                href="{{ route('panels.sale.invoice.index') }}"
                :current="request()->routeIs('panels.sale.invoice.*')"
                wire:navigate
            >
                {{ __('app.invoices') }}
            </flux:sidebar.item>
        @endcan

        @can('sales_item_index')
            <flux:sidebar.item
                icon="package"
                href="{{ route('panels.sale.item.index') }}"
                :current="request()->routeIs('panels.sale.item.*') && ! request()->routeIs('panels.sale.item-price.*')"
                wire:navigate
            >
                {{ __('app.items') }}
            </flux:sidebar.item>
        @endcan

        @can('sales_item_price_index')
            <flux:sidebar.item
                icon="chart-candlestick"
                href="{{ route('panels.sale.item-price.index') }}"
                :current="request()->routeIs('panels.sale.item-price.*')"
                wire:navigate
            >
                {{ __('app.price_notes') }}
            </flux:sidebar.item>
        @endcan

        @can('sales_party_index')
            <flux:sidebar.item
                icon="users"
                href="{{ route('panels.sale.party.index') }}"
                :current="request()->routeIs('panels.sale.party.*')"
                wire:navigate
            >
                {{ __('app.customers') }}
            </flux:sidebar.item>
        @endcan
    </flux:sidebar.nav>

    <flux:sidebar.spacer />

    @include('partials.panels')

    @include('partials.user-dropdown')
    @include('partials.theme-icon')
</flux:sidebar>

<flux:header class="panel-shell-header block! dark:bg-zinc-900 dark:border-zinc-700">
    <div class="flex w-full flex-col gap-2 px-3 py-2 lg:px-4">
        @include('partials.user-navbar')

        <div class="flex w-full items-start gap-2">
            @can('sales_item_index')
                <div class="w-full max-w-3xl">
                    <livewire:panels.sale.item.search :key="'panels-sale-item-search'" />
                </div>
            @endcan

            @can('sales_invoice_create')
                <div class="ms-auto shrink-0">
                    <livewire:panels.sale.temporary-invoice :key="'panels-sale-temporary-invoice'" />
                </div>
            @endcan
        </div>
    </div>
</flux:header>

<flux:main>
    {{ $slot }}
</flux:main>

@role('administrator')
<div class="fixed bottom-4 left-4 z-50">
    <flux:tooltip content="{{ __('app.route_logs.title') }}">
        <flux:button icon="history" variant="ghost" x-on:click="$dispatch('modal-show', { name: 'user-route-logs' })" />
    </flux:tooltip>
</div>
<livewire:user-route-logs />
@endrole

@include('partials.foot')
</body>
</html>
