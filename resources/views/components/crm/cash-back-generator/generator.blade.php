<?php

use App\Jobs\SetareganCo\CashBackGeneratorJob;
use App\Livewire\Forms\Crm\CashBackGeneratorForm;
use Flux\Flux;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new class extends Component
{
    public CashBackGeneratorForm $form;

    public function mount(): void
    {
        $this->form->sms_text = __('app.cash_back_sms');
    }

    public function save(): void
    {
        $this->authorize('crm_cash_back_generator_create');

        $this->form->normalizeAmounts();
        $validated = $this->form->validate();

        $fromDate = Jalalian::fromFormat('Y/m/d', $validated['from_date'])
            ->toCarbon()
            ->startOfDay()
            ->toDateTimeString();

        CashBackGeneratorJob::dispatch(
            $fromDate,
            (int) $validated['min_order_count'],
            (int) $validated['min_total_amount'],
            (int) $validated['discount_amount'],
            (int) $validated['usage_duration_days'],
            $validated['sms_text'],
        );

        $this->form->reset();
        $this->form->sms_text = __('app.cash_back_sms');

        $this->dispatch('panels.crm.cash-back-generator.index.render');
        Flux::modal('panels.crm.cash-back-generator.generator.modal')->close();
        Flux::toast(__('app.cash_back_generator_job_started'));
    }
};
?>

<flux:modal name="panels.crm.cash-back-generator.generator.modal" flyout position="right" class="md:w-[28rem]">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.cash_back_generator') }}</flux:heading>
            <flux:subheading>{{ __('app.cash_back_generator_description') }}</flux:subheading>
        </div>

        <x-date-select
            wire:model="form.from_date"
            :label="__('app.cash_back_generator_from_date')"
            :required="true"
        />

        <flux:input
            type="number"
            wire:model="form.min_order_count"
            label="{{ __('app.cash_back_generator_min_order_count') }}"
            min="1"
        />

        <flux:input
            type="text"
            inputmode="numeric"
            wire:model="form.min_total_amount"
            label="{{ __('app.cash_back_generator_min_total_amount') }}"
            mask:dynamic="$money($input, '.', ',', 0)"
        />

        <flux:input
            type="text"
            inputmode="numeric"
            wire:model="form.discount_amount"
            label="{{ __('app.cash_back_amount') }}"
            mask:dynamic="$money($input, '.', ',', 0)"
        />

        <flux:input
            type="number"
            wire:model="form.usage_duration_days"
            label="{{ __('app.cash_back_usage_duration_days') }}"
            min="1"
        />

        <flux:textarea
            wire:model="form.sms_text"
            label="{{ __('app.cash_back_generator_sms_text') }}"
            description="{{ __('app.cash_back_generator_sms_placeholders') }}"
            rows="6"
        />

        <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
    </form>
</flux:modal>
