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
        @can('service_center_dashboard_index')
        <flux:sidebar.item icon="gauge" href="{{ route('panel.service-center.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        @endcan
        @can('service_center_assembly_index')
        <flux:sidebar.item icon="pc-case" badge="12" href="{{ route('panel.service-center.assembly.index') }}" wire:navigate>{{ __('app.assemblies') }}</flux:sidebar.item>
        @endcan
        @can('service_center_repair_index')
        <flux:sidebar.item icon="repairs" href="{{ route('panel.service-center.repair.index') }}" wire:navigate>{{ __('app.repairs') }}</flux:sidebar.item>
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
@include('partials.foot')
</body>
</html>
