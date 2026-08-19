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
        <flux:sidebar.item icon="home" href="{{ route('panels.administrator.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        <flux:sidebar.group expandable heading="{{ __('app.user_management') }}" class="grid" :expanded="request()->routeIs('panels.administrator.user-management.*')">
            <flux:sidebar.item href="{{ route('panels.administrator.user-management.user.index') }}" wire:navigate>{{ __('app.users') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panels.administrator.user-management.role.index') }}" wire:navigate>{{ __('app.roles') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panels.administrator.user-management.permission.index') }}" wire:navigate>{{ __('app.permissions') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panels.administrator.user-management.device.index') }}" wire:navigate>{{ __('app.devices') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panels.administrator.user-management.hr.index') }}" wire:navigate>{{ __('app.hr_report') }}</flux:sidebar.item>
        </flux:sidebar.group>
        <flux:sidebar.item icon="megaphone" href="{{ route('panels.administrator.announcement.index') }}" wire:navigate>{{ __('app.announcements') }}</flux:sidebar.item>
        @can('administrator_calender_index')
            <flux:sidebar.item icon="calendar" href="{{ route('panels.administrator.calender.index') }}" wire:navigate>{{ __('app.calender_days') }}</flux:sidebar.item>
        @endcan
        @can('administrator_item_day_check')
            <flux:sidebar.item icon="shield-check" href="{{ route('panels.administrator.item-day-check.index') }}" :current="request()->routeIs('panels.administrator.item-day-check.*')" wire:navigate>{{ __('app.day_check.admin_title') }}</flux:sidebar.item>
        @endcan
        <flux:sidebar.group expandable heading="{{ __('app.setting_management') }}" class="grid" :expanded="request()->routeIs('panels.administrator.setting-management.*')">
            <flux:sidebar.item href="{{ route('panels.administrator.setting-management.function.index') }}" wire:navigate>{{ __('app.functions') }}</flux:sidebar.item>
            <flux:sidebar.item href="{{ route('panels.administrator.setting-management.option.index') }}" wire:navigate>{{ __('app.options') }}</flux:sidebar.item>
        </flux:sidebar.group>
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
