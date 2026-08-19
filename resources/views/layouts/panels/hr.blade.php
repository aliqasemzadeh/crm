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
        <flux:sidebar.item icon="clock" href="{{ route('panels.hr.attendance.index') }}" :current="request()->routeIs('panels.hr.attendance.*')" wire:navigate>{{ __('app.attendance') }}</flux:sidebar.item>
        <flux:sidebar.item icon="history" href="{{ route('panels.hr.history.index') }}" :current="request()->routeIs('panels.hr.history.*')" wire:navigate>{{ __('app.attendance_history') }}</flux:sidebar.item>
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

@include('partials.foot')
</body>
</html>
