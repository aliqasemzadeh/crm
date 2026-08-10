<?php

use App\Jobs\Notification\SendSmsMessageJob;
use App\Jobs\SetareganCo\CashBackGeneratorJob;
use App\Livewire\Forms\Crm\ReturnDiscountGenerateForm;
use App\Models\Sepidar\GNR\Party;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ReturnDiscountGenerateForm $form;

    public ?int $partyId = null;

    public string $recipient_name = '';

    public string $recipient_phone = '';

    public string $national_code = '';

    public string $test_mobile = '';

    #[On('panels.crm.return.generate.assign-data')]
    public function assignData(int $partyId): void
    {
        $this->authorize('crm_return_generate');

        $phoneSub = DB::connection('sqlsrv')
            ->table('GNR.PartyPhone')
            ->selectRaw('PartyRef, MAX(Phone) as Phone')
            ->where('IsMain', 1)
            ->groupBy('PartyRef');

        $party = Party::query()
            ->from('GNR.Party as p')
            ->leftJoinSub($phoneSub, 'ph', 'ph.PartyRef', '=', 'p.PartyId')
            ->where('p.PartyId', $partyId)
            ->where('p.IsCustomer', 1)
            ->select([
                'p.PartyId',
                'p.Name',
                'p.LastName',
                'p.IdentificationCode',
                'ph.Phone as main_phone',
            ])
            ->firstOrFail();

        $this->partyId = (int) $party->PartyId;
        $this->recipient_name = trim(implode(' ', array_filter([
            trim((string) $party->Name),
            trim((string) $party->LastName),
        ])));
        $this->recipient_phone = trim((string) ($party->main_phone ?? ''));
        $this->national_code = trim((string) ($party->IdentificationCode ?? ''));

        $this->resetFormDefaults();

        Flux::modal('panels.crm.return.generate.modal')->show();
    }

    public function save(): void
    {
        $this->authorize('crm_return_generate');

        if (! $this->partyId) {
            return;
        }

        $this->form->normalizeAmounts();
        $validated = $this->form->validate();

        $nationalCode = trim($this->national_code);
        $mobile = trim($this->recipient_phone);

        if ($nationalCode === '') {
            Flux::toast(__('app.return_discount_missing_national_code'));

            return;
        }

        if ($mobile === '') {
            Flux::toast(__('app.return_discount_missing_phone'));

            return;
        }

        if (CashBackGeneratorJob::hasUnusedCashBackCode($nationalCode)) {
            Flux::toast(__('app.return_discount_unused_code_exists'));

            return;
        }

        $createdCode = CashBackGeneratorJob::createCashBackCode(
            $nationalCode,
            (int) $validated['discount_amount'],
            (int) $validated['usage_duration_days'],
            (bool) ($validated['for_special_offer'] ?? false),
        );

        $message = CashBackGeneratorJob::buildMessage(
            $validated['sms_text'],
            trim((string) ($validated['site_url'] ?? '')),
            $createdCode['code'],
            (int) $validated['discount_amount'],
            $createdCode['from'],
            $createdCode['to'],
            $this->recipient_name !== '' ? $this->recipient_name : __('app.cash_back_generator_test_name'),
            (int) $validated['usage_duration_days'],
        );

        SendSmsMessageJob::dispatch($mobile, $message);

        $this->resetRecipient();
        Flux::modal('panels.crm.return.generate.modal')->close();
        Flux::toast(__('app.return_discount_sent_successfully'));
    }

    public function sendTestSms(): void
    {
        $this->authorize('crm_return_generate');

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
            $this->recipient_name !== '' ? $this->recipient_name : __('app.cash_back_generator_test_name'),
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
    }

    private function resetRecipient(): void
    {
        $this->partyId = null;
        $this->recipient_name = '';
        $this->recipient_phone = '';
        $this->national_code = '';
        $this->resetFormDefaults();
    }
};
?>

<flux:modal name="panels.crm.return.generate.modal" flyout position="right" class="md:w-[28rem]">
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('app.return_send_discount') }}</flux:heading>
                <flux:subheading>{{ __('app.return_send_discount_description') }}</flux:subheading>
            </div>

            <flux:callout variant="secondary" icon="user">
                <div class="space-y-1 text-sm">
                    <div>{{ $recipient_name !== '' ? $recipient_name : '—' }}</div>
                    <div class="tabular-nums" dir="ltr">{{ $recipient_phone !== '' ? $recipient_phone : '—' }}</div>
                    <div class="tabular-nums" dir="ltr">{{ $national_code !== '' ? $national_code : '—' }}</div>
                </div>
            </flux:callout>

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

            <flux:button
                type="submit"
                variant="primary"
                color="orange"
                class="w-full"
                icon="gift"
                wire:confirm="{{ __('app.are_you_sure_to_send_sms') }}"
            >
                {{ __('app.return_send_discount') }}
            </flux:button>
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
