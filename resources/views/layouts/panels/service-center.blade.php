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
        <flux:sidebar.item
            icon="pc-case"
            badge="{{ \App\Models\ServiceCenter\Assembly::query()->whereIn('status', ['pending', 'in_progress'])->count() }}"
            href="{{ route('panels.service-center.assembly.index') }}"
            wire:navigate
        >{{ __('app.assemblies') }}</flux:sidebar.item>
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
    @persist('toast')
        <flux:toast />
    @endpersist
@else
    @persist('toast')
        <flux:toast />
    @endpersist
@endrole
</body>
</html>
