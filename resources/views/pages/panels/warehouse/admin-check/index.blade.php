<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Warehouse\DayCheck;
use App\Models\Sepidar\INV\Item;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCheckId;
    public $admin_comment = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function dayChecks()
    {
        return DayCheck::with('user')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($uq) {
                    $uq->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function selectedCheck()
    {
        if (!$this->selectedCheckId) return null;
        return DayCheck::find($this->selectedCheckId);
    }

    #[Computed]
    public function checkItems()
    {
        if (!$this->selectedCheck) return collect();
        return Item::whereIn('ItemID', $this->selectedCheck->items)->get();
    }

    public function openCheck($id)
    {
        $check = DayCheck::find($id);
        if (!$check) return;

        $this->selectedCheckId = $id;
        $this->admin_comment = $check->admin_comment;
        $this->modal('admin-check-modal')->show();
    }

    public function approve()
    {
        if (!Auth::user()->hasPermissionTo('warehouse_item_day_check_admin')) return;

        $check = DayCheck::find($this->selectedCheckId);
        $check->update([
            'status' => 'approve',
            'admin_comment' => $this->admin_comment,
            'approve_at' => now(),
        ]);

        $this->modal('admin-check-modal')->close();
        Flux::toast(__('app.day_check.admin_check.status_updated'));
    }

    public function reject()
    {
        if (!Auth::user()->hasPermissionTo('warehouse_item_day_check_admin')) return;

        $check = DayCheck::find($this->selectedCheckId);
        $check->update([
            'status' => 'reject',
            'admin_comment' => $this->admin_comment,
            'reject_at' => now(),
        ]);

        $this->modal('admin-check-modal')->close();
        Flux::toast(__('app.day_check.admin_check.status_updated'));
    }
};
?>

<div>
    <x-slot name="title">
        {{ __('app.day_check.admin_check.title') }}
    </x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('app.day_check.admin_check.title') }}</flux:heading>
        </div>

        <flux:card>
            <div class="flex mb-4">
                <flux:input wire:model.live="search" icon="search" placeholder="{{ __('app.day_check.admin_check.search_placeholder') }}" />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('app.day_check.user') }}</flux:table.column>
                    <flux:table.column>{{ __('app.day_check.items_count') }}</flux:table.column>
                    <flux:table.column>{{ __('app.day_check.status') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->dayChecks as $check)
                        <flux:table.row :key="$check->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar src="{{ $check->user->getAvatarUrl() }}" size="xs" />
                                    <span>{{ $check->user->name }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ count($check->items) }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $color = match($check->status) {
                                        'check' => 'zinc',
                                        'send' => 'blue',
                                        'approve' => 'green',
                                        'reject' => 'red',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge color="{{ $color }}">
                                    {{ __('app.day_check.' . $check->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell dir="ltr" class="text-right">
                                {{ \Morilog\Jalali\Jalalian::fromDateTime($check->created_at)->format('Y/m/d H:i') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="{{ __('app.day_check.admin_check.view_details') }}">
                                    <flux:button size="xs" variant="primary" color="teal" icon="eye" wire:click="openCheck({{ $check->id }})" />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $this->dayChecks->links() }}
            </div>
        </flux:card>
    </div>

    <flux:modal name="admin-check-modal" flyout position="right" class="w-full max-w-4xl">
        <div class="space-y-6 h-full flex flex-col">
            <div>
                <flux:heading size="lg">{{ __('app.day_check.admin_check.view_details') }}</flux:heading>
                <flux:subheading>
                    {{ $this->selectedCheck?->user->name }} - {{ $this->selectedCheck ? \Morilog\Jalali\Jalalian::fromDateTime($this->selectedCheck->created_at)->format('Y/m/d') : '' }}
                </flux:subheading>
            </div>

            <div class="space-y-6 flex-1 overflow-y-auto">
                @if($this->selectedCheck)
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        @foreach($this->checkItems as $item)
                            @php
                                $expected = (float)($this->selectedCheck->item_stocks[$item->ItemID] ?? 0);
                                $actual = isset($this->selectedCheck->item_checks[$item->ItemID]) && $this->selectedCheck->item_checks[$item->ItemID] !== '' ? (float)$this->selectedCheck->item_checks[$item->ItemID] : null;
                                $hasDiff = $actual !== null && $actual != $expected;
                            @endphp
                            <flux:card class="p-4 {{ $hasDiff ? 'border-red-500 bg-red-50 dark:bg-red-900/10' : '' }}">
                                <div class="flex flex-col gap-3">
                                    <div class="w-full aspect-square bg-zinc-100 rounded-lg overflow-hidden relative">
                                        @if($item->image?->Thumbnail)
                                            <img
                                                src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                                                alt="{{ $item->Title }}"
                                                class="w-full h-full object-cover"
                                            />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <flux:icon icon="image-off" class="text-zinc-400" />
                                            </div>
                                        @endif

                                        @if($hasDiff)
                                            <div class="absolute top-2 right-2">
                                                <flux:badge color="red" size="sm" icon="circle-alert" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <flux:field>
                                            <flux:label>{{ __('app.day_check.item_name') }}</flux:label>
                                            <div class="font-bold text-sm truncate">{{ $item->Name }}</div>
                                            <flux:description>{{ $item->Number }}</flux:description>
                                        </flux:field>
                                        <div class="flex flex-col mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                            <div class="flex justify-between text-xs">
                                                <span class="text-zinc-500">{{ __('app.day_check.expected_stock') }}:</span>
                                                <span class="font-medium">{{ number_format($expected) }}</span>
                                            </div>
                                            <div class="flex justify-between text-xs mt-1">
                                                <span class="text-zinc-500">{{ __('app.day_check.actual_stock') }}:</span>
                                                <span class="font-bold {{ $hasDiff ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ $actual !== null ? number_format($actual) : '---' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <flux:textarea
                            label="{{ __('app.day_check.user_comment') }}"
                            value="{{ $this->selectedCheck->user_comment }}"
                            readonly
                        />

                        <flux:textarea
                            label="{{ __('app.day_check.admin_comment') }}"
                            wire:model="admin_comment"
                            :disabled="in_array($this->selectedCheck->status, ['approve', 'reject'])"
                        />
                    </div>

                    @if($this->selectedCheck->status === 'send')
                        <div class="flex gap-4">
                            <flux:button variant="primary" color="green" class="flex-1" wire:click="approve" icon="check-circle">
                                {{ __('app.day_check.approve_check') }}
                            </flux:button>
                            <flux:button variant="primary" color="red" class="flex-1" wire:click="reject" icon="x-circle">
                                {{ __('app.day_check.reject_check') }}
                            </flux:button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </flux:modal>
</div>
