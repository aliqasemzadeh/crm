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
        @can('accounting_dashboard_index')
        <flux:sidebar.item icon="layout-dashboard"
                           href="{{ route('panels.accounting.dashboard.index') }}"
                           wire:navigate>
            {{ __('app.dashboard') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_bank_index')
        <flux:sidebar.item icon="building-2"
                           href="{{ route('panels.accounting.bank.index') }}"
                           wire:navigate>
            {{ __('app.banks') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_invoice_index')
        <flux:sidebar.item icon="file-text"
                           href="{{ route('panels.accounting.invoice.index') }}"
                           wire:navigate>
            {{ __('app.invoices') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_inventory_receipt_index')
        <flux:sidebar.item icon="clipboard-list"
                           href="{{ route('panels.accounting.inventory-receipt.index') }}"
                           wire:navigate>
            {{ __('app.inventory_receipts') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_grouping_index')
        <flux:sidebar.item icon="boxes"
                           href="{{ route('panels.accounting.grouping.index') }}"
                           wire:navigate>
            {{ __('app.inventory') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_item_index')
            <flux:sidebar.item icon="package"
                               href="{{ route('panels.accounting.item.index') }}"
                               wire:navigate>
                {{ __('app.items') }}
            </flux:sidebar.item>
        @endcan

        @can('accounting_party_index')
        <flux:sidebar.item icon="users"
                           href="{{ route('panels.accounting.party.index') }}"
                           wire:navigate>
            {{ __('app.customers') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_payment_header_index')
        <flux:sidebar.item icon="credit-card"
                           href="{{ route('panels.accounting.payment-header.index') }}"
                           wire:navigate>
            {{ __('app.expenses') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_receipt_header_index')
        <flux:sidebar.item icon="receipt"
                           href="{{ route('panels.accounting.receipt-header.index') }}"
                           wire:navigate>
            {{ __('app.receipts') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_receipt_cheque_index')
        <flux:sidebar.item icon="wallet"
                           href="{{ route('panels.accounting.receipt-cheque.index') }}"
                           wire:navigate>
            {{ __('app.receipt_cheques') }}
        </flux:sidebar.item>
        @endcan

        @can('accounting_payment_cheque_index')
        <flux:sidebar.item icon="badge-dollar-sign"
                           href="{{ route('panels.accounting.payment-cheque.index') }}"
                           wire:navigate>
            {{ __('app.payment_cheques') }}
        </flux:sidebar.item>
        @endcan

            @can('accounting_price_note_index')
                <flux:sidebar.item icon="chart-candlestick"
                                   href="{{ route('panels.accounting.price-note.index') }}"
                                   :current="request()->routeIs('panels.accounting.price-note.*')"
                                   wire:navigate>
                    {{ __('app.price_notes') }}
                </flux:sidebar.item>
            @endcan

            @can('accounting_account_index')
                <flux:sidebar.item icon="book-open"
                                   href="{{ route('panels.accounting.account.index') }}"
                                   :current="request()->routeIs('panels.accounting.account.*')"
                                   wire:navigate>
                    {{ __('app.accounts') }}
                </flux:sidebar.item>
            @endcan

            @can('accounting_tax_index')
                <flux:sidebar.item icon="calculator"
                                   href="{{ route('panels.accounting.tax.index') }}"
                                   :current="request()->routeIs('panels.accounting.tax.*')"
                                   wire:navigate>
                    {{ __('app.tax_report') }}
                </flux:sidebar.item>
            @endcan

            @can('accounting_full_report_index')
            <flux:sidebar.item icon="calculator"
                               href="{{ route('panels.accounting.full-report.index') }}"
                               :current="request()->routeIs('panels.accounting.full-report.*')"
                               wire:navigate>
                {{ __('app.full_account_report') }}
            </flux:sidebar.item>
            @endcan

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
<livewire:crm.call.send-sms :key="'panels-accounting-send-sms'" />
@include('partials.foot')
</body>
</html>

