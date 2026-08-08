<?php

use App\Livewire\Forms\Crm\CashBackRuleForm;
use App\Models\Crm\SetareganCo\CashBackRule;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public CashBackRuleForm $form;

    public ?int $ruleId = null;

    #[On('panels.crm.cash-back-rule.edit.assign-data')]
    public function assignData(int $id): void
    {
        $rule = CashBackRule::query()->findOrFail($id);

        $this->ruleId = $rule->id;
        $this->form->start_amount = $rule->start_amount;
        $this->form->end_amount = $rule->end_amount;
        $this->form->cash_back_amount = $rule->cash_back_amount;
        $this->form->is_percent = (bool) $rule->is_percent;
        $this->form->activation_delay_days = $rule->activation_delay_days;
        $this->form->usage_duration_days = $rule->usage_duration_days;

        Flux::modal('panels.crm.cash-back-rule.edit.modal')->show();
    }

    public function save(): void
    {
        $this->authorize('crm_cash_back_rule_edit');

        if (! $this->ruleId) {
            return;
        }

        $validated = $this->form->validate();

        CashBackRule::query()->findOrFail($this->ruleId)->update($validated);

        $this->dispatch('panels.crm.cash-back-rule.index.render');
        Flux::modal('panels.crm.cash-back-rule.edit.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.cash_back_rule')]));
    }
};
?>

<flux:modal name="panels.crm.cash-back-rule.edit.modal" flyout position="right" class="md:w-96">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.edit_cash_back_rule') }}</flux:heading>
            <flux:subheading>{{ __('app.cash_back_rules_description') }}</flux:subheading>
        </div>

        <flux:input type="number" wire:model="form.start_amount" label="{{ __('app.cash_back_start_amount') }}" />
        <flux:input type="number" wire:model="form.end_amount" label="{{ __('app.cash_back_end_amount') }}" />
        <flux:input type="number" wire:model="form.cash_back_amount" label="{{ __('app.cash_back_amount') }}" />

        <flux:field variant="inline">
            <flux:label>{{ __('app.cash_back_is_percent') }}</flux:label>
            <flux:switch wire:model="form.is_percent" />
            <flux:error name="form.is_percent" />
        </flux:field>

        <flux:input type="number" wire:model="form.activation_delay_days" label="{{ __('app.cash_back_activation_delay_days') }}" />
        <flux:input type="number" wire:model="form.usage_duration_days" label="{{ __('app.cash_back_usage_duration_days') }}" />

        <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
    </form>
</flux:modal>
