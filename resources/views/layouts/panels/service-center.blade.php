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
        @can('service_center_dashboard_index')
        <flux:sidebar.item icon="gauge" href="{{ route('panels.service-center.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        @endcan
        @can('service_center_assembly_index')
        <flux:sidebar.item icon="pc-case" badge="12" href="{{ route('panels.service-center.assembly.index') }}" wire:navigate>{{ __('app.assemblies') }}</flux:sidebar.item>
        @endcan
        @can('service_center_repair_index')
        <flux:sidebar.item icon="toolbox" href="{{ route('panels.service-center.repair.index') }}" wire:navigate>{{ __('app.repairs') }}</flux:sidebar.item>
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
