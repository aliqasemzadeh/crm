<?php

use App\Jobs\Notification\SendSmsMessageJob;
use App\Jobs\SetareganCo\CashBackGeneratorJob;
use App\Livewire\Forms\Crm\CashBackGeneratorForm;
use Flux\Flux;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new class extends Component
{
    public CashBackGeneratorForm $form;

    public string $test_mobile = '';

    public ?int $estimated_customers = null;

    public function mount(): void
    {
        $this->form->sms_text = __('app.cash_back_sms');
        $this->form->site_url = 'https://setaregan.co';
    }

    public function estimateCustomers(): void
    {
        $this->authorize('crm_cash_back_generator_create');

        $this->form->normalizeAmounts();

        $validated = $this->validate([
            'form.from_date' => ['required', 'string'],
            'form.min_order_count' => ['required', 'integer', 'min:1'],
            'form.min_total_amount' => ['required', 'integer', 'min:0'],
        ], [], [
            'form.from_date' => __('app.cash_back_generator_from_date'),
            'form.min_order_count' => __('app.cash_back_generator_min_order_count'),
            'form.min_total_amount' => __('app.cash_back_generator_min_total_amount'),
        ]);

        $fromDate = Jalalian::fromFormat('Y/m/d', $validated['form']['from_date'])
            ->toCarbon()
            ->startOfDay()
            ->toDateTimeString();

        $this->estimated_customers = CashBackGeneratorJob::estimateEligibleCustomerCount(
            $fromDate,
            (int) $validated['form']['min_order_count'],
            (int) $validated['form']['min_total_amount'],
        );

        Flux::toast(__('app.cash_back_generator_estimate_result', [
            'count' => number_format($this->estimated_customers),
        ]));
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
            trim((string) ($validated['site_url'] ?? '')),
            (bool) ($validated['for_special_offer'] ?? false),
        );

        $this->resetFormDefaults();

        $this->dispatch('panels.crm.cash-back-generator.index.render');
        Flux::modal('panels.crm.cash-back-generator.generator.modal')->close();
        Flux::toast(__('app.cash_back_generator_job_started'));
    }

    public function sendTestSms(): void
    {
        $this->authorize('crm_cash_back_generator_create');

        $this->form->normalizeAmounts();

        $this->validate([
            'test_mobile' => ['required', 'string', 'min:10', 'max:15'],
            'form.sms_text' => ['required', 'string', 'min:3'],
            'form.discount_amount' => ['required', 'integer', 'min:1'],
            'form.usage_duration_days' => ['required', 'integer', 'min:1'],
            'form.site_url' => ['nullable', 'string', 'max:255'],
        ], [], [
            'test_mobile' => __('app.cash_back_generator_test_mobile'),
            'form.sms_text' => __('app.cash_back_generator_sms_text'),
            'form.discount_amount' => __('app.cash_back_amount'),
            'form.usage_duration_days' => __('app.cash_back_usage_duration_days'),
            'form.site_url' => __('app.cash_back_generator_site_url'),
        ]);

        $fromDate = now()->startOfDay();
        $toDate = $fromDate->copy()->addDays((int) $this->form->usage_duration_days)->endOfDay();

        $message = CashBackGeneratorJob::buildMessage(
            $this->form->sms_text,
            trim((string) $this->form->site_url),
            'SRSCBTESTCODE01',
            (int) $this->form->discount_amount,
            $fromDate,
            $toDate,
            __('app.cash_back_generator_test_name'),
            (int) $this->form->usage_duration_days,
        );

        SendSmsMessageJob::dispatch(trim($this->test_mobile), $message);

        Flux::toast(__('app.sms_sent_successfully'));
    }

    private function resetFormDefaults(): void
    {
        $this->form->reset();
        $this->form->sms_text = __('app.cash_back_sms');
        $this->form->site_url = 'https://setaregan.co';
        $this->form->for_special_offer = false;
        $this->test_mobile = '';
        $this->estimated_customers = null;
    }
};
?>

<flux:modal name="panels.crm.cash-back-generator.generator.modal" flyout position="right" class="md:w-[28rem]">
    <div class="space-y-6">
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

            <flux:field variant="inline">
                <flux:label>{{ __('app.cash_back_generator_for_special_offer') }}</flux:label>
                <flux:description>{{ __('app.cash_back_generator_for_special_offer_help') }}</flux:description>
                <flux:switch wire:model="form.for_special_offer" />
                <flux:error name="form.for_special_offer" />
            </flux:field>

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

            @if ($estimated_customers !== null)
                <flux:badge color="cyan" size="sm" class="w-full justify-center">
                    {{ __('app.cash_back_generator_estimate_result', ['count' => number_format($estimated_customers)]) }}
                </flux:badge>
            @endif

            <div class="space-y-3">
                <flux:button
                    type="button"
                    variant="primary"
                    color="cyan"
                    class="w-full"
                    icon="users"
                    wire:click="estimateCustomers"
                >
                    {{ __('app.cash_back_generator_estimate') }}
                </flux:button>

                <flux:button type="submit" variant="primary" color="orange" class="w-full" icon="gift">
                    {{ __('app.cash_back_generator_run') }}
                </flux:button>
            </div>
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
