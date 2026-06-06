<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Warehouse\DayCheck;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemImage;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $selectedCheckId;
    public $item_checks = [];
    public $user_comment = '';
    public $admin_comment = '';
    public $activeTab = 'items';

    protected $listeners = ['refresh' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function dayChecks()
    {
        $query = DayCheck::with('user')->latest();

        if (!Auth::user()->hasPermissionTo('warehouse_item_day_check_admin')) {
             $query->where('user_id', Auth::id());
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })->orWhere('status', 'like', '%' . $this->search . '%');
        }

        return $query->paginate(10);
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
        $this->selectedCheckId = $id;
        $check = DayCheck::find($id);
        $this->item_checks = $check->item_checks ?? array_fill_keys($check->items, '');
        $this->user_comment = $check->user_comment;
        $this->admin_comment = $check->admin_comment;
        $this->showModal = true;
    }

    public function submitCheck()
    {
        $check = DayCheck::find($this->selectedCheckId);

        // Basic validation: all items must have a value
        foreach ($check->items as $itemId) {
            if (!isset($this->item_checks[$itemId]) || $this->item_checks[$itemId] === '') {
                 Flux::toast(__('common.please_fill_all_fields'), variant: 'danger');
                 return;
            }
        }

        $check->update([
            'item_checks' => $this->item_checks,
            'user_comment' => $this->user_comment,
            'status' => 'send',
            'check_at' => now(),
        ]);

        $this->showModal = false;
        Flux::toast(__('app.saved_successfully', ['name' => __('app.day_check.title')]));
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

        $this->showModal = false;
        Flux::toast(__('app.day_check.approve_check'));
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

        $this->showModal = false;
        Flux::toast(__('app.day_check.reject_check'));
    }
};
?>

<div>
    <flux:main>
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
                                <flux:badge color="{{ $color }}" inset="false">
                                    {{ __('app.day_check.' . $check->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell dir="ltr" class="text-right">
                                {{ \Morilog\Jalali\Jalalian::fromDateTime($check->created_at)->format('Y/m/d H:i') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:tooltip content="{{ __('app.day_check.view_items') }}">
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
    </flux:main>

    <flux:modal wire:model="showModal" flyout position="right" class="w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.day_check.view_items') }}</flux:heading>
                <flux:subheading>{{ $this->selectedCheck?->user->name }} - {{ $this->selectedCheck ? \Morilog\Jalali\Jalalian::fromDateTime($this->selectedCheck->created_at)->format('Y/m/d') : '' }}</flux:subheading>
            </div>

            <div class="space-y-4">
                @if($this->selectedCheck)
                    <div class="max-h-[60vh] overflow-y-auto space-y-4 pr-2">
                        @foreach($this->checkItems as $item)
                            <flux:card class="p-4">
                                <div class="flex gap-4">
                                    <div class="w-20 h-20 flex-shrink-0 bg-zinc-100 rounded overflow-hidden">
                                        @php
                                            $image = $item->image;
                                        @endphp
                                        @if($image)
                                            <img src="data:image/jpeg;base64,{{ base64_encode($image->Thumbnail) }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-zinc-400">
                                                <flux:icon icon="image" size="lg" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 space-y-1">
                                        <div class="font-medium text-sm">{{ $item->Name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $item->Number }}</div>

                                        @if(Auth::user()->hasPermissionTo('warehouse_item_day_check_admin') || in_array($this->selectedCheck->status, ['approve', 'reject']))
                                            <div class="text-xs font-semibold text-teal-600 mt-1">
                                                {{ __('app.day_check.expected_stock') }}: {{ number_format($this->selectedCheck->item_stocks[$item->ItemID] ?? 0) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <flux:input
                                        type="number"
                                        label="{{ __('app.day_check.actual_stock') }}"
                                        wire:model.defer="item_checks.{{ $item->ItemID }}"
                                        :disabled="$this->selectedCheck->status !== 'check' && $this->selectedCheck->status !== 'reject'"
                                    />
                                </div>
                            </flux:card>
                        @endforeach
                    </div>

                    <flux:textarea
                        label="{{ __('app.day_check.user_comment') }}"
                        wire:model.defer="user_comment"
                        :disabled="$this->selectedCheck->status !== 'check' && $this->selectedCheck->status !== 'reject'"
                    />

                    @if(Auth::user()->hasPermissionTo('warehouse_item_day_check_admin') || $this->selectedCheck->admin_comment)
                        <flux:textarea
                            label="{{ __('app.day_check.admin_comment') }}"
                            wire:model.defer="admin_comment"
                            :disabled="!Auth::user()->hasPermissionTo('warehouse_item_day_check_admin') || in_array($this->selectedCheck->status, ['approve', 'reject'])"
                        />
                    @endif

                    <div class="flex gap-2">
                        @if(($this->selectedCheck->status === 'check' || $this->selectedCheck->status === 'reject') && $this->selectedCheck->user_id === Auth::id())
                            <flux:button variant="primary" color="orange" class="w-full" wire:click="submitCheck">
                                {{ __('app.day_check.submit_check') }}
                            </flux:button>
                        @endif

                        @if($this->selectedCheck->status === 'send' && Auth::user()->hasPermissionTo('warehouse_item_day_check_admin'))
                            <flux:button variant="primary" color="green" class="w-full" wire:click="approve">
                                {{ __('app.day_check.approve_check') }}
                            </flux:button>
                            <flux:button variant="primary" color="red" class="w-full" wire:click="reject">
                                {{ __('app.day_check.reject_check') }}
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
