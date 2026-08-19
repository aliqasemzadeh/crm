<?php

use App\Models\UserDevice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.panels.administrator')] class extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    #[Computed]
    public function devices()
    {
        return UserDevice::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';
                $query->where('token', 'like', $search)
                    ->orWhere('ip', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhereHas('user', fn($q) => $q->where('first_name', 'like', $search)->orWhere('last_name', 'like', $search));
            })
            ->latest()
            ->paginate(20);
    }

    public function approve(int $id): void
    {
        $this->authorize('administrator_user_management_device');
        $device = UserDevice::findOrFail($id);
        $device->update(['is_approved' => true]);

        $device->records()->where('is_device_approved', false)->update(['is_device_approved' => true]);

        Flux::toast(__('app.device_approved'));
    }

    public function reject(int $id): void
    {
        $this->authorize('administrator_user_management_device');
        $device = UserDevice::findOrFail($id);
        $device->update(['is_approved' => false]);

        $device->records()->where('is_device_approved', true)->update(['is_device_approved' => false]);

        Flux::toast(__('app.device_rejected'));
    }

    public function deleteDevice(int $id): void
    {
        $this->authorize('administrator_user_management_device');
        UserDevice::findOrFail($id)->delete();
        Flux::toast(__('app.device_deleted'));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->authorize('administrator_user_management_device');
    }
};

?>

<x-slot name="title">
    {{ __('app.devices') }}
</x-slot>
<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.devices') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.devices_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:table :paginate="$this->devices">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="5" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input size="sm" placeholder="{{ __('app.search_placeholder') }}" wire:model.live="search" />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.user') }}</flux:table.column>
            <flux:table.column>{{ __('app.device_name') }}</flux:table.column>
            <flux:table.column>{{ __('app.ip') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column>{{ __('app.actions') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->devices as $device)
                <flux:table.row :key="$device->id">
                    <flux:table.cell>{{ $device->user?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $device->name ?: __('app.unknown') }}</flux:table.cell>
                    <flux:table.cell>{{ $device->ip }}</flux:table.cell>
                    <flux:table.cell>
                        @if($device->is_approved)
                            <flux:badge color="green">{{ __('app.approved') }}</flux:badge>
                        @else
                            <flux:badge color="red">{{ __('app.not_approved') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <div class="flex gap-2">
                            @if(!$device->is_approved)
                                <flux:tooltip content="{{ __('app.approve') }}">
                                    <flux:button size="xs" variant="primary" color="green" icon="check" icon:variant="outline" wire:click="approve({{ $device->id }})" />
                                </flux:tooltip>
                            @else
                                <flux:tooltip content="{{ __('app.reject') }}">
                                    <flux:button size="xs" variant="primary" color="orange" icon="x" icon:variant="outline" wire:click="reject({{ $device->id }})" />
                                </flux:tooltip>
                            @endif
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button size="xs" variant="primary" color="red" icon="trash" icon:variant="outline" wire:click="deleteDevice({{ $device->id }})" wire:confirm="{{ __('common.are_you_sure') }}" />
                            </flux:tooltip>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
