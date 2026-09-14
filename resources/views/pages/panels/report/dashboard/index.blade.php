<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.report')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('report_dashboard_index');
    }
};
?>

<x-slot name="title">
    {{ __('app.report_dashboard_title') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.report_dashboard_title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.report_dashboard_subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <flux:callout icon="chart-column">
        <flux:callout.text>{{ __('app.report_coming_soon') }}</flux:callout.text>
    </flux:callout>

    <div class="mt-6 flex flex-wrap gap-3">
        @can('report_site_index')
            <flux:button variant="primary" color="teal" href="{{ route('panels.report.site.index') }}" wire:navigate icon="globe-alt" icon:variant="outline">
                {{ __('app.report_site_title') }}
            </flux:button>
        @endcan
        @can('report_item_index')
            <flux:button variant="primary" color="sky" href="{{ route('panels.report.item.index') }}" wire:navigate icon="boxes" icon:variant="outline">
                {{ __('app.report_item_title') }}
            </flux:button>
        @endcan
    </div>
</div>
