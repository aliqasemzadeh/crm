<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="{{ __('app.direction') }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="panel-shell-sidebar dark:bg-zinc-900 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <flux:sidebar.search placeholder="Search..." />

    <flux:sidebar.nav>
        @can('report_dashboard_index')
            <flux:sidebar.item icon="home" href="{{ route('panels.report.dashboard.index') }}" :current="request()->routeIs('panels.report.dashboard.*')" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        @endcan
        @can('report_site_index')
            <flux:sidebar.item icon="globe-alt" href="{{ route('panels.report.site.index') }}" :current="request()->routeIs('panels.report.site.*')" wire:navigate>{{ __('app.website') }}</flux:sidebar.item>
        @endcan
        @can('report_item_index')
            <flux:sidebar.item icon="boxes" href="{{ route('panels.report.item.index') }}" :current="request()->routeIs('panels.report.item.*')" wire:navigate>{{ __('app.items') }}</flux:sidebar.item>
        @endcan
    </flux:sidebar.nav>

    <flux:sidebar.spacer />

    @include('partials.panels')

    @include('partials.user-dropdown')
    @include('partials.theme-icon')
</flux:sidebar>

<flux:header class="panel-shell-header block! dark:bg-zinc-900 dark:border-zinc-700">
    @include('partials.user-navbar')
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
