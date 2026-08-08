<?php

use App\Models\Crm\SetareganCo\CashBackRule;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

return new #[Layout('layouts.panels.crm')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('crm_cash_back_rule_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('panels.crm.cash-back-rule.index.render')]
    public function refresh(): void
    {
        unset($this->cashBackRules);
    }

    #[Computed]
    public function cashBackRules()
    {
        return CashBackRule::query()
            ->when($this->search !== '', function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('start_amount', 'like', "%{$search}%")
                        ->orWhere('end_amount', 'like', "%{$search}%")
                        ->orWhere('cash_back_amount', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        $this->authorize('crm_cash_back_rule_delete');

        CashBackRule::query()->findOrFail($id)->delete();

        Flux::toast(__('app.deleted_successfully', ['name' => __('app.cash_back_rule')]));
        unset($this->cashBackRules);
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.cash_back_rules') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.cash_back_rules_description') }}</flux:subheading>
            </div>
            @can('crm_cash_back_rule_create')
                <flux:modal.trigger name="panels.crm.cash-back-rule.create.modal">
                    <flux:button variant="primary" color="teal" icon="badge-percent">{{ __('app.create_cash_back_rule') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
        <flux:separator variant="subtle" />
    </div>

    <livewire:crm.cash-back-rule.create :key="'cash-back-rule-create'" />
    <livewire:crm.cash-back-rule.edit :key="'cash-back-rule-edit'" />

    <flux:table :paginate="$this->cashBackRules">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="7" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live="search"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.id') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_start_amount') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_end_amount') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_amount') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_activation_delay_days') }}</flux:table.column>
            <flux:table.column>{{ __('app.cash_back_usage_duration_days') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->cashBackRules as $rule)
                <flux:table.row :key="$rule->id">
                    <flux:table.cell>{{ $rule->id }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($rule->start_amount) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($rule->end_amount) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($rule->cash_back_amount) }}
                        @if($rule->is_percent)
                            <flux:badge color="blue" size="sm" class="ms-1">{{ __('app.percent') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm" class="ms-1">{{ __('app.fixed_amount') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $rule->activation_delay_days }}</flux:table.cell>
                    <flux:table.cell>{{ $rule->usage_duration_days }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @can('crm_cash_back_rule_edit')
                            <flux:tooltip content="{{ __('app.edit') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="orange"
                                    icon="pencil"
                                    icon:variant="outline"
                                    wire:click="$dispatch('panels.crm.cash-back-rule.edit.assign-data', { id: {{ $rule->id }} })"
                                />
                            </flux:tooltip>
                        @endcan
                        @can('crm_cash_back_rule_delete')
                            <flux:tooltip content="{{ __('app.delete') }}">
                                <flux:button
                                    size="xs"
                                    variant="primary"
                                    color="red"
                                    icon="trash"
                                    icon:variant="outline"
                                    wire:click="delete({{ $rule->id }})"
                                    wire:confirm="{{ __('app.are_you_sure') }}"
                                />
                            </flux:tooltip>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center py-4">
                        {{ __('app.no_records_found') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
