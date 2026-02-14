<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="rtl">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <flux:sidebar.search placeholder="{{ __('app.search_placeholder') }}" />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.bank.index') }}" wire:navigate>{{ __('app.banks') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate>{{ __('app.invoices') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.inventory-receipt.index') }}" wire:navigate>{{ __('app.inventory_receipts') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.grouping.index') }}" wire:navigate>{{ __('app.inventory') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.party.index') }}" wire:navigate>{{ __('app.customer') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.payment-header.index') }}" wire:navigate>{{ __('app.expenses') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.receipt-header.index') }}" wire:navigate>{{ __('app.receipts') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.receipt-cheque.index') }}" wire:navigate>{{ __('app.receipt_cheques') }}</flux:sidebar.item>
        <flux:sidebar.item icon="home" href="{{ route('panels.accounting.payment-cheque.index') }}" wire:navigate>{{ __('app.payment_cheques') }}</flux:sidebar.item>
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

