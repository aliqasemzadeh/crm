<flux:modal name="panels.accounting.receipt-cheque.view.modal" class="md:w-1/3" flyout position="right">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('app.receipt_cheque_details') }}</flux:heading>
            <flux:text class="mt-2">{{ __('app.receipt_cheque_details_description') }}</flux:text>
        </div>

        @if(isset($cheque))
            <div class="grid grid-cols-1 gap-4">
                <flux:card>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.number') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->Number }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.sayad_code') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->SayadCode ?? '-' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.customer') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->dl->Title ?? $cheque->DlRef }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.amount') }}:</flux:text>
                            <flux:text class="font-bold">{{ number_format($cheque->Amount) }} {{ __('app.rial') }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.date') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.status') }}:</flux:text>
                            @php
                                $passed = \App\Models\Sepidar\RPA\ChequeState::receiptPassed($cheque->State);
                                $overdue = ! $passed && $cheque->Date && \Illuminate\Support\Carbon::parse($cheque->Date)->startOfDay()->lt(now()->startOfDay());
                            @endphp
                            <flux:badge size="sm" color="{{ $passed ? 'green' : ($overdue ? 'red' : 'amber') }}">
                                {{ $passed ? __('app.cheque_passed') : __('app.cheque_unpassed') }}
                                — {{ \App\Models\Sepidar\RPA\ChequeState::receiptLabel($cheque->State) }}
                            </flux:badge>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.cheque_bank_account') }}:</flux:text>
                            <flux:text class="font-bold">{{ $cheque->latestBankingItem?->bankAccount?->displayName() ?? '-' }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text>{{ __('app.cheque_account_balance') }}:</flux:text>
                            <flux:text class="font-bold">
                                {{ $cheque->latestBankingItem?->bankAccount ? number_format($cheque->latestBankingItem->bankAccount->Balance).' '.__('app.rial') : '-' }}
                            </flux:text>
                        </div>
                        <flux:separator />
                        <div class="pt-2">
                            <flux:text>{{ __('app.description') }}:</flux:text>
                            <flux:text class="block mt-1">{{ $cheque->Description ?? '-' }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>
</flux:modal>
