<?php

use App\Jobs\Notification\SendSmsMessageJob;
use App\Jobs\SetareganCo\CashBackGeneratorJob;
use App\Livewire\Forms\Crm\CashBackRuleForm;
use App\Models\Crm\SetareganCo\CashBackRule;
use App\Support\PersianAmountFormatter;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public CashBackRuleForm $form;

    public string $test_mobile = '';

    public function mount(): void
    {
        $this->resetFormDefaults();
    }

    public function save(): void
    {
        $this->authorize('crm_cash_back_rule_create');

        $this->form->normalizeAmounts();
        $validated = $this->form->validate();

        CashBackRule::query()->create($validated);

        $this->resetFormDefaults();

        $this->dispatch('panels.crm.cash-back-rule.index.render');
        Flux::modal('panels.crm.cash-back-rule.create.modal')->close();
        Flux::toast(__('app.saved_successfully', ['name' => __('app.cash_back_rule')]));
    }

    public function sendTestSms(): void
    {
        $this->authorize('crm_cash_back_rule_create');

        $this->form->normalizeAmounts();

        $this->validate([
            'test_mobile' => ['required', 'string', 'min:10', 'max:15'],
            'form.sms_text' => ['required', 'string', 'min:3'],
            'form.cash_back_amount' => ['required', 'integer', 'min:1'],
            'form.usage_duration_days' => ['required', 'integer', 'min:1'],
            'form.activation_delay_days' => ['required', 'integer', 'min:0'],
            'form.site_url' => ['nullable', 'string', 'max:255'],
        ], [], [
            'test_mobile' => __('app.cash_back_generator_test_mobile'),
            'form.sms_text' => __('app.cash_back_generator_sms_text'),
            'form.cash_back_amount' => __('app.cash_back_amount'),
            'form.usage_duration_days' => __('app.cash_back_usage_duration_days'),
            'form.activation_delay_days' => __('app.cash_back_activation_delay_days'),
            'form.site_url' => __('app.cash_back_generator_site_url'),
        ]);

        $fromDate = now()->addDays((int) $this->form->activation_delay_days)->startOfDay();
        $toDate = $fromDate->copy()->addDays((int) $this->form->usage_duration_days)->endOfDay();

        [$amountLabel, $amountCharacter] = $this->amountPlaceholders();

        $message = CashBackGeneratorJob::buildMessage(
            $this->form->sms_text,
            trim((string) $this->form->site_url),
            'SRSCBTESTCODE01',
            (int) $this->form->cash_back_amount,
            $fromDate,
            $toDate,
            __('app.cash_back_generator_test_name'),
            (int) $this->form->usage_duration_days,
            $amountLabel,
            $amountCharacter,
        );

        SendSmsMessageJob::dispatch(trim($this->test_mobile), $message);

        Flux::toast(__('app.sms_sent_successfully'));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function amountPlaceholders(): array
    {
        $amount = (int) $this->form->cash_back_amount;

        if ($this->form->is_percent) {
            $label = number_format($amount).'%';

            return [$label, $label.' '.__('app.percent')];
        }

        return [
            number_format($amount).' '.__('app.toman'),
            PersianAmountFormatter::formatCharacter($amount),
        ];
    }

    private function resetFormDefaults(): void
    {
        $this->form->reset();
        $this->form->is_percent = false;
        $this->form->sms_text = __('app.cash_back_sms');
        $this->form->site_url = 'https://setaregan.co';
        $this->test_mobile = '';
    }
};
?>

<flux:modal name="panels.crm.cash-back-rule.create.modal" flyout position="right" class="md:w-[28rem]">
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.create_cash_back_rule') }}</flux:heading>
                <flux:subheading>{{ __('app.cash_back_rules_description') }}</flux:subheading>
            </div>

            <flux:input
                type="text"
                inputmode="numeric"
                wire:model="form.start_amount"
                label="{{ __('app.cash_back_start_amount') }}"
                mask:dynamic="$money($input, '.', ',', 0)"
            />
            <flux:input
                type="text"
                inputmode="numeric"
                wire:model="form.end_amount"
                label="{{ __('app.cash_back_end_amount') }}"
                mask:dynamic="$money($input, '.', ',', 0)"
            />
            <flux:input
                type="text"
                inputmode="numeric"
                wire:model="form.cash_back_amount"
                label="{{ __('app.cash_back_amount') }}"
                mask:dynamic="$money($input, '.', ',', 0)"
            />

            <flux:field variant="inline">
                <flux:label>{{ __('app.cash_back_is_percent') }}</flux:label>
                <flux:switch wire:model="form.is_percent" />
                <flux:error name="form.is_percent" />
            </flux:field>

            <flux:input type="number" wire:model="form.activation_delay_days" label="{{ __('app.cash_back_activation_delay_days') }}" />
            <flux:input type="number" wire:model="form.usage_duration_days" label="{{ __('app.cash_back_usage_duration_days') }}" />

            <flux:textarea
                wire:model="form.sms_text"
                label="{{ __('app.cash_back_generator_sms_text') }}"
                description="{{ __('app.cash_back_generator_sms_placeholders') }}"
                rows="6"
            />

            <flux:input
                type="url"
                wire:model="form.site_url"
                label="{{ __('app.cash_back_generator_site_url') }}"
                description="{{ __('app.cash_back_generator_site_url_help') }}"
                placeholder="https://setaregan.co"
            />

            <flux:button type="submit" variant="primary" color="orange" class="w-full">{{ __('app.save') }}</flux:button>
        </form>

        <flux:separator variant="subtle" />

        <div class="space-y-4">
            <div>
                <flux:heading size="sm">{{ __('app.cash_back_generator_test_sms') }}</flux:heading>
                <flux:subheading>{{ __('app.cash_back_generator_test_sms_help') }}</flux:subheading>
            </div>

            <flux:input
                type="tel"
                wire:model="test_mobile"
                label="{{ __('app.cash_back_generator_test_mobile') }}"
                placeholder="09xxxxxxxxx"
                dir="ltr"
            />

            <flux:button
                type="button"
                variant="primary"
                color="teal"
                class="w-full"
                icon="send"
                wire:click="sendTestSms"
                wire:confirm="{{ __('app.are_you_sure_to_send_sms') }}"
            >
                {{ __('app.cash_back_generator_send_test_sms') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
