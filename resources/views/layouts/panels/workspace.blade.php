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
        <flux:sidebar.item icon="home" href="{{ route('panels.workspace.dashboard.index') }}" wire:navigate>{{ __('app.dashboard') }}</flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" href="{{ route('panels.workspace.task.index') }}" wire:navigate>{{ __('app.tasks') }}</flux:sidebar.item>
        <flux:sidebar.item icon="shopping-cart" href="{{ route('panels.workspace.purchase-request.index') }}" wire:navigate>{{ __('app.purchase_requests') }}</flux:sidebar.item>
        <flux:sidebar.item icon="files" href="{{ route('panels.workspace.instruction.index') }}" wire:navigate>{{ __('app.instructions') }}</flux:sidebar.item>
        @can('workspace_review_index')
        <flux:sidebar.item icon="list-check" href="{{ route('panels.workspace.review.index') }}" wire:navigate>{{ __('app.reviews') }}</flux:sidebar.item>
        <flux:sidebar.item icon="users" href="{{ route('panels.workspace.review.user.index') }}" wire:navigate>{{ __('app.workspace_review.users_list') }}</flux:sidebar.item>
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
