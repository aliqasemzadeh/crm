<flux:sidebar.nav>
    @can('user_access')
        <flux:sidebar.item icon="user" href="{{ route('panels.user.dashboard.index') }}" wire:navigate>{{ __('app.user_panel') }}</flux:sidebar.item>
    @endcan

        @can('workspace_access')
            <flux:sidebar.item icon="columns-3-cog" href="{{ route('panels.workspace.dashboard.index') }}" wire:navigate>{{ __('app.workspace') }}</flux:sidebar.item>
        @endcan
        @can('administrator_access')
            <flux:sidebar.item icon="user-star" href="{{ route('panels.administrator.dashboard.index') }}" wire:navigate>{{ __('app.administrator') }}</flux:sidebar.item>
        @endcan
        @can('accounting_access')
            <flux:sidebar.item icon="chart-no-axes-combined" href="{{ route('panels.accounting.dashboard.index') }}" wire:navigate>{{ __('app.accounting') }}</flux:sidebar.item>
        @endcan
    @can('service_center_access')
    <flux:sidebar.item icon="cpu" href="{{ route('panels.service-center.dashboard.index') }}" wire:navigate>{{ __('app.service_center') }}</flux:sidebar.item>
    @endcan

    @can('crm_access')
    <flux:sidebar.item icon="handshake" href="{{ route('panels.crm.dashboard.index') }}" wire:navigate>{{ __('app.crm') }}</flux:sidebar.item>
    @endcan


</flux:sidebar.nav>
