<x-slot name="title">
    {{ __('app.payment_cheques') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.payment_cheques') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.payment_cheques_description') }}</flux:subheading>
            </div>
        </div>

        <flux:separator variant="subtle" />
    </div>

    @include('livewire.panels.accounting.partials.cheque-months', [
        'yearTotalLabel' => __('app.total_annual_payment_cheques'),
    ])

    <livewire:panels.accounting.payment-cheque.view :key="'payment-cheque-view'" />

    @include('livewire.panels.accounting.partials.cheque-table', [
        'cheques' => $this->cheques,
        'searchPlaceholder' => __('app.search_in_payment_cheques'),
        'idField' => 'PaymentChequeId',
        'viewEvent' => 'panels.accounting.payment-cheque.view.assign-data',
    ])
</div>
