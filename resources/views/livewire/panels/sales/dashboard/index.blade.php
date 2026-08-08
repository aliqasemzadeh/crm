<div>
    <div class="relative mb-6 w-full">
        <div>
            <flux:heading size="xl" level="1">{{ __('app.sales') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('app.sales_dashboard_description') }}</flux:subheading>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:button variant="primary" color="orange" href="{{ route('panels.sale.dashboard.index') }}" wire:navigate>
        {{ __('app.dashboard') }}
    </flux:button>
</div>
