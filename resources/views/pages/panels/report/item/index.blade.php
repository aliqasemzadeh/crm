<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.panels.report')] class extends Component
{
    public function mount(): void
    {
        $this->authorize('report_item_index');
    }
};
?>

<x-slot name="title">
    {{ __('app.report_item_title') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('app.report_item_title') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('app.report_item_subtitle') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <flux:callout icon="boxes">
        <flux:callout.text>{{ __('app.report_coming_soon') }}</flux:callout.text>
    </flux:callout>
</div>
