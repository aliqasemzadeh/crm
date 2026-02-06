<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="{{ __('app.direction') }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <flux:sidebar.search placeholder="Search..." />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" href="{{ route('panels.user.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        <flux:sidebar.item icon="key" href="{{ route('panels.user.setting.change-password') }}" wire:navigate>{{ __('app.change_password') }}</flux:sidebar.item>
        <flux:sidebar.item icon="at-sign" href="{{ route('panels.user.setting.change-email') }}" wire:navigate>{{ __('app.change_email') }}</flux:sidebar.item>
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
