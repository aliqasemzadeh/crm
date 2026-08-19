<flux:sidebar.nav>
    @can('user_access')
        <flux:sidebar.item icon="user" href="{{ route('panels.user.dashboard.index') }}" :current="request()->routeIs('panels.user.*')" wire:navigate>{{ __('app.user_panel') }}</flux:sidebar.item>
    @endcan

        @can('workspace_access')
            <flux:sidebar.item icon="columns-3-cog" href="{{ route('panels.workspace.dashboard.index') }}" :current="request()->routeIs('panels.workspace.*')"  wire:navigate>{{ __('app.workspace') }}</flux:sidebar.item>
        @endcan
        @can('sales_access')
            <flux:sidebar.item icon="shopping-bag" href="{{ route('panels.sale.dashboard.index') }}" :current="request()->routeIs('panels.sale.*')" wire:navigate>{{ __('app.sales') }}</flux:sidebar.item>
        @endcan
        @can('administrator_access')
            <flux:sidebar.item icon="user-star" href="{{ route('panels.administrator.dashboard.index') }}" :current="request()->routeIs('panels.administrator.*')" wire:navigate>{{ __('app.administrator') }}</flux:sidebar.item>
        @endcan
        @can('accounting_access')
            <flux:sidebar.item icon="chart-no-axes-combined" href="{{ route('panels.accounting.dashboard.index') }}" :current="request()->routeIs('panels.accounting.*')" wire:navigate>{{ __('app.accounting') }}</flux:sidebar.item>
        @endcan
    @can('service_center_access')
    <flux:sidebar.item icon="cpu" href="{{ route('panels.service-center.dashboard.index') }}" :current="request()->routeIs('panels.service-center.*')" wire:navigate>{{ __('app.service_center') }}</flux:sidebar.item>
    @endcan

    @can('warehouse_access')
        <flux:sidebar.item icon="package" href="{{ route('panels.warehouse.dashboard.index') }}" :current="request()->routeIs('panels.warehouse.*')" wire:navigate>{{ __('app.warehouse') }}</flux:sidebar.item>
    @endcan

    @can('crm_access')
    <flux:sidebar.item icon="handshake" href="{{ route('panels.crm.dashboard.index') }}" :current="request()->routeIs('panels.crm.*')" wire:navigate>{{ __('app.crm') }}</flux:sidebar.item>
    @endcan

    <flux:sidebar.item icon="clock" href="{{ route('panels.hr.attendance.index') }}" :current="request()->routeIs('panels.hr.*')" wire:navigate>{{ __('app.hr') }}</flux:sidebar.item>



</flux:sidebar.nav>
