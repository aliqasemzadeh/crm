<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="rtl">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
<flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    @include('partials.sidebar-header')

    <livewire:main.sidebar.categories />

    <flux:sidebar.spacer />

    @include('partials.panels')
    @include('partials.user-dropdown')
    @include('partials.theme-icon')

</flux:sidebar>

<flux:header sticky class="bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <flux:navbar class="w-full flex flex-row">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left"/>

        <livewire:main.header.search/>

        <flux:spacer/>

        <livewire:main.sidebar.basket/>

        @auth
            <flux:button icon="user" href="{{ route('logout') }}" square variant="ghost"/>
        @endauth

        @guest
            <flux:button icon="user" href="{{ route('login') }}" square variant="ghost"/>
        @endguest

    </flux:navbar>
</flux:header>

<flux:main class="p-2 md:p-6">
    {{ $slot }}
</flux:main>
    @include('partials.foot')
    <flux:toast />
</body>
</html>
