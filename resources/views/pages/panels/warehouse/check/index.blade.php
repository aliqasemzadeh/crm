<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Warehouse\DayCheck;
use App\Jobs\UpdateDayCheckItemJob;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemImage;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCheckId;
    public $item_checks = [];
    public $mismatchedItems = [];
    public $user_comment = '';
    public $admin_comment = '';
    public $activeTab = 'items';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedItemChecks()
    {
        $this->save();
    }

    #[Computed]
    public function dayChecks()
    {
        $query = DayCheck::with('user')->latest();

        $query->where('user_id', Auth::id());

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                })->orWhere('status', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(10);
    }

    #[Computed]
    public function selectedCheck()
    {
        if (!$this->selectedCheckId) return null;
        $query = DayCheck::query();
        $query->where('user_id', Auth::id());
        return $query->find($this->selectedCheckId);
    }

    #[Computed]
    public function checkItems()
    {
        if (!$this->selectedCheck) return collect();
        return Item::whereIn('ItemID', $this->selectedCheck->items)->get();
    }

    public function openCheck($id)
    {
        $query = DayCheck::query();
        $query->where('user_id', Auth::id());
        $check = $query->find($id);

        if (!$check) {
            Flux::toast(__('common.not_found'), variant: 'danger');
            return;
        }

        $this->selectedCheckId = $id;
        $this->mismatchedItems = [];
        $this->item_checks = $check->item_checks ?? [];

        // Ensure all items have a value in item_checks array
        foreach ($check->items as $itemId) {
            if (!isset($this->item_checks[$itemId])) {
                $this->item_checks[$itemId] = '';
            }
        }

        $this->user_comment = $check->user_comment;
        $this->admin_comment = $check->admin_comment;
        $this->modal('day-check-modal')->show();
    }

    public function save()
    {
        if (!$this->selectedCheckId) return;

        $check = $this->selectedCheck;
        if (!$check) return;
        if ($check->status !== 'check' && $check->status !== 'reject') return;

        UpdateDayCheckItemJob::dispatch(
            (int) $this->selectedCheckId,
            $this->item_checks,
            $this->user_comment,
        );
    }

    public function submitCheck()
    {
        $check = $this->selectedCheck;
        if (!$check) return;

        // Basic validation: all items must have a value
        foreach ($check->items as $itemId) {
            if (!isset($this->item_checks[$itemId]) || $this->item_checks[$itemId] === '') {
                 Flux::toast(__('common.please_fill_all_fields'), variant: 'danger');
                 return;
            }
        }

        // Check each item against the system stock and mark mismatches
        $this->mismatchedItems = [];
        $stocks = $check->item_stocks ?? [];
        foreach ($check->items as $itemId) {
            $expected = (float) ($stocks[$itemId] ?? 0);
            $actual = (float) $this->item_checks[$itemId];
            if ($actual != $expected) {
                $this->mismatchedItems[] = $itemId;
            }
        }

        $check->update([
            'item_checks' => $this->item_checks,
            'user_comment' => $this->user_comment,
            'status' => 'send',
            'check_at' => now(),
        ]);

        if (!empty($this->mismatchedItems)) {
            Flux::toast(__('app.day_check.mismatch_found'), variant: 'warning');
            return;
        }

        $this->modal('day-check-modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.day_check.title')]));
    }
};
?>

<div>
    <x-slot name="title">
        {{ __('app.day_check.title') }}
    </x-slot>

    <div>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">{{ __('app.day_check.title') }}</flux:heading>
        </div>

        <flux:card>
             <div class="flex mb-4">
                <flux:input wire:model.live="search" icon="search" placeholder="{{ __('app.day_check.search_placeholder') }}" />
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
                                <div class="flex items-center gap-2">
                                    <flux:tooltip content="{{ __('app.day_check.view_items') }}">
                                        <flux:button size="xs" variant="primary" color="teal" icon="eye" wire:click="openCheck({{ $check->id }})" />
                                    </flux:tooltip>
                                </div>
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

    <flux:modal name="day-check-modal" flyout position="right" class="w-full max-w-4xl" wire:poll.60s="save">
        <div class="space-y-6 h-full flex flex-col">
            <div>
                <flux:heading size="lg">{{ __('app.day_check.view_items') }}</flux:heading>
                <flux:subheading>{{ $this->selectedCheck?->user->name }} - {{ $this->selectedCheck ? \Morilog\Jalali\Jalalian::fromDateTime($this->selectedCheck->created_at)->format('Y/m/d') : '' }}</flux:subheading>
            </div>

            <div class="space-y-6 flex-1 overflow-y-auto">
                @if($this->selectedCheck)
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        @foreach($this->checkItems as $item)
                            @php
                                $isAdmin = Auth::user()->hasPermissionTo('warehouse_item_day_check_admin');
                                $expected = (float)($this->selectedCheck->item_stocks[$item->ItemID] ?? 0);
                                $actual = isset($this->item_checks[$item->ItemID]) && $this->item_checks[$item->ItemID] !== '' ? (float)$this->item_checks[$item->ItemID] : null;
                                $hasDiff = ($isAdmin && $actual !== null && $actual != $expected) || in_array($item->ItemID, $this->mismatchedItems);
                            @endphp
                            <flux:card class="p-4 {{ $hasDiff ? 'border-red-500 bg-red-50 dark:bg-red-900/10' : '' }}">
                                <div class="flex gap-4">
                                    <div class="w-16 h-16 flex-shrink-0 bg-zinc-100 rounded overflow-hidden relative">
                                        @if($item->image?->Thumbnail)
                                            <img
                                                src="data:image/jpeg;base64,{{ base64_encode($item->image->Thumbnail) }}"
                                                alt="{{ $item->Title }}"
                                                class="w-full h-full shrink-0 rounded-md object-cover border border-zinc-200 dark:border-zinc-700"
                                            />
                                        @else
                                            <div class="w-full h-full shrink-0 bg-zinc-100 dark:bg-zinc-800 rounded-md flex items-center justify-center">
                                                <flux:icon icon="image-off" class="text-zinc-400" />
                                            </div>
                                        @endif

                                        @if($hasDiff)
                                            <div class="absolute inset-0 bg-red-500/20 flex items-center justify-center">
                                                <flux:icon icon="circle-alert" class="text-red-600" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <flux:field>
                                            <flux:label>{{ __('app.day_check.item_name') }}</flux:label>
                                            <div class="font-medium text-sm">{{ $item->Title }}</div>
                                            <flux:description>{{ $item->Code }}</flux:description>
                                        </flux:field>

                                        <div class="flex justify-between items-center mt-2">
                                            @if($actual !== null && $isAdmin)
                                                <div class="text-xs font-semibold {{ $hasDiff ? 'text-red-600' : 'text-zinc-600' }}">
                                                    {{ __('app.day_check.actual_stock') }}: {{ number_format($actual) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if(($this->selectedCheck->status === 'check' || $this->selectedCheck->status === 'reject') && $this->selectedCheck->user_id === Auth::id())
                                    <div class="mt-4">
                                        <flux:input
                                            type="number"
                                            size="sm"
                                            label="{{ __('app.day_check.actual_stock') }}"
                                            wire:model.live="item_checks.{{ $item->ItemID }}"
                                        />
                                    </div>
                                @endif
                            </flux:card>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <flux:textarea
                            label="{{ __('app.day_check.user_comment') }}"
                            wire:model="user_comment"
                            :disabled="$this->selectedCheck->status !== 'check' && $this->selectedCheck->status !== 'reject'"
                        />

                        @if(Auth::user()->hasPermissionTo('warehouse_item_day_check_admin') || $this->selectedCheck->admin_comment)
                            <flux:textarea
                                label="{{ __('app.day_check.admin_comment') }}"
                                wire:model="admin_comment"
                                :disabled="!Auth::user()->hasPermissionTo('warehouse_item_day_check_admin') || in_array($this->selectedCheck->status, ['approve', 'reject'])"
                            />
                        @endif
                    </div>

                    <div class="flex flex-col gap-2">
                        @if(($this->selectedCheck->status === 'check' || $this->selectedCheck->status === 'reject') && $this->selectedCheck->user_id === Auth::id())
                            <flux:button variant="primary" color="orange" class="w-full" wire:click="submitCheck">
                                {{ __('app.day_check.submit_check') }}
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
