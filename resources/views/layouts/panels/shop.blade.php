<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="rtl">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <flux:sidebar.search placeholder="Search..." />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="gauge" href="{{ route('panel.shop.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        <flux:sidebar.item icon="boxes" href="{{ route('panel.shop.product.index') }}" wire:navigate>{{ __('app.products') }}</flux:sidebar.item>
        <flux:sidebar.item icon="bring-to-front" href="{{ route('panel.shop.order.index') }}" wire:navigate>{{ __('app.orders') }}</flux:sidebar.item>

        <flux:sidebar.group expandable heading="{{ __('app.setting_management') }}" class="grid" :expanded="request()->routeIs('panel.shop.setting-management.*')">
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.category.index') }}" wire:navigate>{{ __('app.categories') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.brand.index') }}" wire:navigate>{{ __('app.brands') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.warranty.index') }}" wire:navigate>{{ __('app.warranties') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.color.index') }}" wire:navigate>{{ __('app.colors') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.unit.index') }}" wire:navigate>{{ __('app.units') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.attribute-group.index') }}" wire:navigate>{{ __('app.attribute_groups') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.setting-management.attribute.index') }}" wire:navigate>{{ __('app.attributes') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group expandable heading="{{ __('app.shipping') }}" class="grid" :expanded="request()->routeIs('panel.shop.shipping.*')">
            <flux:sidebar.item href="{{ route('panel.shop.shipping.method.index') }}" wire:navigate>{{ __('app.shipping_methods') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.shipping.zone.index') }}" wire:navigate>{{ __('app.shipping_zones') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.shipping.rate.index') }}" wire:navigate>{{ __('app.shipping_rates') }}</flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group expandable heading="{{ __('app.accounting') }}" class="grid" :expanded="request()->routeIs('panel.shop.sepidar.*')">
            <flux:sidebar.item href="{{ route('panel.shop.sepidar.grouping.index') }}" wire:navigate>{{ __('app.inventory') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.sepidar.invoice.index') }}" wire:navigate>{{ __('app.invoices') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panel.shop.sepidar.party.index') }}" wire:navigate>{{ __('app.customers') }}</flux:sidebar.item>
        </flux:sidebar.group>

    </flux:sidebar.nav>

    <flux:sidebar.spacer />

    @include('partials.panels')

    @include('partials.user-dropdown')
    @include('partials.theme-icon')
</flux:sidebar>

<flux:header class="block! bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    @include('partials.user-navbar')
</flux:header>

<flux:main>
    {{ $slot }}
</flux:main>
@include('partials.foot')
</body>
</html>
