<?php

use App\Models\ServiceCenter\Repair;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.customer')] class extends Component {
    public Repair $repair;

    public function mount(Repair $repair): void
    {
        $this->repair = $repair;
    }
};
?>

<x-slot name="title">
    {{ __('app.repair_status_tracking') }} #{{ $repair->admission_code }}
</x-slot>

<div class="space-y-6 p-4">
    <div>
        <flux:heading size="xl">{{ __('app.repair_status_tracking') }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">
            {{ __('app.admission_code') }}: {{ $repair->admission_code }}
        </flux:subheading>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.status') }}</flux:text>
            <div class="mt-1">
                <flux:badge variant="solid" color="{{ $repair->status_enum->color() }}">
                    {{ $repair->status_enum->label() }}
                </flux:badge>
            </div>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.date') }}</flux:text>
            <flux:heading size="sm" class="mt-1">
                {{ $repair->created_at ? Jalalian::fromCarbon($repair->created_at)->format('Y/m/d H:i') : '-' }}
            </flux:heading>
        </div>
        <div class="flex flex-col">
            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('app.estimate_date') }}</flux:text>
            <flux:heading size="sm" class="mt-1">
                {{ $repair->estimate_date ? Jalalian::fromCarbon($repair->estimate_date)->format('Y/m/d') : '-' }}
            </flux:heading>
        </div>
    </div>

    <flux:card>
        <flux:heading size="sm" class="mb-3">{{ __('app.device_information') }}</flux:heading>
        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('app.device_name') }}</flux:text>
                <div class="font-medium">{{ $repair->device_name }}</div>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('app.device_serial_number') }}</flux:text>
                <div class="font-medium">{{ $repair->device_serial_number ?: '-' }}</div>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('app.owner_name') }}</flux:text>
                <div class="font-medium">{{ $repair->owner_name }}</div>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('app.owner_mobile') }}</flux:text>
                <div class="font-medium">{{ $repair->owner_mobile }}</div>
            </div>
        </div>
    </flux:card>

    @if (filled($repair->status_description))
        <flux:callout>
            <flux:callout.heading>{{ __('app.status_description') }}</flux:callout.heading>
            <flux:callout.text>{{ $repair->status_description }}</flux:callout.text>
        </flux:callout>
    @endif
</div>
