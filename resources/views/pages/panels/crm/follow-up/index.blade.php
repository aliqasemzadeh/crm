<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Crm\FollowUp;
use App\Models\SetareganCo\User as Customer;
use App\Models\User;
use App\Enums\FollowUpStatusEnum;
use Flux\Flux;
use Morilog\Jalali\Jalalian;
use Livewire\Attributes\Layout;

return new #[Layout('layouts.panels.crm')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $customerSearch = '';
    public $agentSearch = '';
    public $agent_id;
    public $status;

    // Form properties
    public $editing = null;
    public $customer_id;
    public $form_agent_id;
    public $form_status;
    public $failure_reason;
    public $description;
    public $due_date;
    public $next_follow_up_date;
    public $parent_id;

    public function mount()
    {
        $this->form_status = FollowUpStatusEnum::PENDING->value;
        $this->form_agent_id = auth()->id();
        $this->due_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function followUps()
    {
        return FollowUp::query()
            ->with(['agent', 'customer.userInfo'])
            ->when($this->search, function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('UserName', 'like', '%' . $this->search . '%')
                      ->orWhere('PhoneNumber', 'like', '%' . $this->search . '%')
                      ->orWhereHas('userInfo', function ($sq) {
                          $sq->where('Name', 'like', '%' . $this->search . '%')
                            ->orWhere('Family', 'like', '%' . $this->search . '%')
                            ->orWhere('Mobile', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->agent_id, fn($q) => $q->where('agent_id', $this->agent_id))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy('due_date', 'desc')
            ->paginate(10);
    }

    #[Computed]
    public function agents()
    {
        return User::query()
            ->when($this->agentSearch, function ($q) {
                $q->where('first_name', 'like', '%' . $this->agentSearch . '%')
                  ->orWhere('last_name', 'like', '%' . $this->agentSearch . '%');
            })
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function customers()
    {
        $excludedCustomerIds = FollowUp::where('created_at', '>=', now()->subDays(30))
            ->pluck('customer_id')
            ->unique()
            ->toArray();

        return Customer::query()
            ->with('userInfo')
            ->whereNotIn('Id', $excludedCustomerIds)
            ->when($this->customerSearch, function ($q) {
                $q->where('UserName', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('PhoneNumber', 'like', '%' . $this->customerSearch . '%')
                  ->orWhereHas('userInfo', function ($sq) {
                      $sq->where('Name', 'like', '%' . $this->customerSearch . '%')
                        ->orWhere('Family', 'like', '%' . $this->customerSearch . '%')
                        ->orWhere('Mobile', 'like', '%' . $this->customerSearch . '%');
                  });
            })
            ->limit(20)
            ->get();
    }

    public function create()
    {
        $this->resetForm();
        Flux::modal('follow-up-modal')->show();
    }

    public function edit(FollowUp $followUp)
    {
        $this->editing = $followUp;
        $this->customer_id = $followUp->customer_id;
        $this->form_agent_id = $followUp->agent_id;
        $this->form_status = $followUp->status->value;
        $this->failure_reason = $followUp->failure_reason;
        $this->description = $followUp->description;
        $this->due_date = $followUp->due_date->format('Y-m-d');
        $this->next_follow_up_date = $followUp->next_follow_up_date ? $followUp->next_follow_up_date->format('Y-m-d') : null;
        $this->parent_id = $followUp->parent_id;

        Flux::modal('follow-up-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'customer_id' => 'required',
            'form_agent_id' => 'required',
            'form_status' => 'required',
            'due_date' => 'required|date',
        ]);

        $data = [
            'customer_id' => $this->customer_id,
            'agent_id' => $this->form_agent_id,
            'status' => $this->form_status,
            'failure_reason' => $this->failure_reason,
            'description' => $this->description,
            'due_date' => $this->due_date,
            'next_follow_up_date' => $this->next_follow_up_date,
            'parent_id' => $this->parent_id,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            Flux::toast(__('app.saved_successfully', ['name' => __('app.follow_up')]));
        } else {
            FollowUp::create($data);
            Flux::toast(__('app.saved_successfully', ['name' => __('app.follow_up')]));
        }

        Flux::modal('follow-up-modal')->close();
        $this->resetForm();
    }

    public function delete(FollowUp $followUp)
    {
        $followUp->delete();
        Flux::toast(__('app.deleted_successfully', ['name' => __('app.follow_up')]));
    }

    protected function resetForm()
    {
        $this->editing = null;
        $this->customer_id = null;
        $this->customerSearch = '';
        $this->agentSearch = '';
        $this->form_agent_id = auth()->id();
        $this->form_status = FollowUpStatusEnum::PENDING->value;
        $this->failure_reason = null;
        $this->description = null;
        $this->due_date = now()->format('Y-m-d');
        $this->next_follow_up_date = null;
        $this->parent_id = null;
    }
};
?>

<div class="space-y-6" x-data>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('app.follow_ups') }}</flux:heading>
                <flux:subheading>{{ __('app.crm_follow_up_description') }}</flux:subheading>
            </div>

            <flux:button variant="primary" color="orange" icon="plus" wire:click="create">{{ __('app.create') }}</flux:button>
        </div>

    <flux:card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:input wire:model.live="search" icon="search" placeholder="{{ __('app.search_placeholder') }}" />

            <flux:select wire:model.live="agent_id" placeholder="{{ __('app.select_agent') }}">
                <option value="">{{ __('app.all') }}</option>
                @foreach($this->agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" placeholder="{{ __('app.select_status') }}">
                <option value="">{{ __('app.all') }}</option>
                @foreach(FollowUpStatusEnum::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    <flux:table :paginate="$this->followUps">
        <flux:table.columns>
            <flux:table.column>{{ __('app.customer') }}</flux:table.column>
            <flux:table.column>{{ __('app.agent') }}</flux:table.column>
            <flux:table.column>{{ __('app.status') }}</flux:table.column>
            <flux:table.column>{{ __('app.due_date') }}</flux:table.column>
            <flux:table.column>{{ __('app.next_follow_up_date') }}</flux:table.column>
            <flux:table.column align="end"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->followUps as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell>
                        <div class="flex flex-col">
                            <span class="font-medium">{{ ($item->customer?->userInfo?->Name || $item->customer?->userInfo?->Family) ? $item->customer->userInfo->Name . ' ' . $item->customer->userInfo->Family : ($item->customer?->UserName ?? 'N/A') }}</span>
                            @if($mobile = ($item->customer?->userInfo?->Mobile ?: $item->customer?->PhoneNumber))
                                @if(! filter_var($mobile, FILTER_VALIDATE_EMAIL))
                                    <span class="text-xs text-zinc-500">{{ $mobile }}</span>
                                @endif
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $item->agent?->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $item->status->color() }}" size="sm" inset="top bottom">
                            {{ $item->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ Jalalian::fromDateTime($item->due_date)->format('Y/m/d H:i') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $item->next_follow_up_date ? Jalalian::fromDateTime($item->next_follow_up_date)->format('Y/m/d H:i') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell align="end">
            <div class="flex justify-end gap-2">
                <flux:tooltip content="{{ __('app.edit') }}">
                    <flux:button size="xs" variant="primary" color="orange" icon="pencil" icon:variant="outline" wire:click="edit({{ $item->id }})" />
                </flux:tooltip>

                <flux:tooltip content="{{ __('app.delete') }}">
                    <flux:button size="xs" variant="primary" color="red" icon="trash" icon:variant="outline" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('app.are_you_sure') }}" />
                </flux:tooltip>
            </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="follow-up-modal" flyout position="right" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editing ? __('app.edit_follow_up') : __('app.create_follow_up') }}</flux:heading>
            </div>

            <div class="space-y-4">
                <flux:select wire:model="customer_id" label="{{ __('app.customer') }}" variant="combobox" :filter="false">
                    <x-slot name="input">
                        <flux:select.input wire:model.live="customerSearch" placeholder="{{ __('app.search_placeholder') }}" />
                    </x-slot>

                    @foreach($this->customers as $cust)
                        <flux:select.option value="{{ $cust->Id }}" wire:key="cust-{{ $cust->Id }}">
                            @php
                                $displayName = ($cust->userInfo?->Name || $cust->userInfo?->Family) ? $cust->userInfo->Name . ' ' . $cust->userInfo->Family : $cust->UserName;
                                $displayMobile = ($cust->userInfo?->Mobile ?: $cust->PhoneNumber);
                                if (filter_var($displayMobile, FILTER_VALIDATE_EMAIL)) {
                                    $displayMobile = null;
                                }
                            @endphp
                            {{ $displayName }} {{ $displayMobile ? "($displayMobile)" : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form_agent_id" label="{{ __('app.agent') }}" variant="combobox" :filter="false">
                    <x-slot name="input">
                        <flux:select.input wire:model.live="agentSearch" placeholder="{{ __('app.search_placeholder') }}" />
                    </x-slot>

                    @foreach($this->agents as $agent)
                        <flux:select.option value="{{ $agent->id }}" wire:key="agent-{{ $agent->id }}">
                            {{ $agent->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form_status" label="{{ __('app.status') }}">
                    @foreach(FollowUpStatusEnum::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:date-picker wire:model="due_date" label="{{ __('app.due_date') }}" />

                <flux:date-picker wire:model="next_follow_up_date" label="{{ __('app.next_follow_up_date') }}" />

                <flux:input wire:model="failure_reason" label="{{ __('app.failure_reason') }}" />

                <flux:textarea wire:model="description" label="{{ __('app.description') }}" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="Flux.modal('follow-up-modal').close()">{{ __('app.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
