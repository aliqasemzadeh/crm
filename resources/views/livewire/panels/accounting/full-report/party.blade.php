@php
    $s = $this->summary;
@endphp

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('app.full_report_party_detail') }}</flux:heading>
            <flux:text class="mt-1 text-lg font-medium text-zinc-700 dark:text-zinc-200">
                {{ $party->Name }} {{ $party->LastName }}
            </flux:text>
            <flux:text size="sm" class="mt-2 max-w-3xl text-zinc-500 dark:text-zinc-400">
                {{ __('app.full_report_party_detail_intro') }}
            </flux:text>
        </div>
        <div class="flex flex-shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
            @if(($s['final_balance'] ?? 0) > 0)
                <flux:tooltip content="{{ __('app.full_report_send_debt_reminder_sms') }}">
                    <flux:button
                        variant="primary"
                        color="orange"
                        icon="message-square-text"
                        icon:variant="outline"
                        wire:click="openDebtReminderSms"
                    >
                        {{ __('app.send_sms') }}
                    </flux:button>
                </flux:tooltip>
            @endif
            <flux:button variant="ghost" icon="arrow-right" href="{{ route('panels.accounting.full-report.index') }}" wire:navigate>
                {{ __('app.back_to_full_report') }}
            </flux:button>
        </div>
    </div>

    @if(!$party->DLRef)
        <flux:callout variant="warning" icon="triangle-alert">
            {{ __('app.full_report_party_no_dl') }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <flux:card class="flex flex-col gap-1 bg-red-50 p-4 dark:bg-red-950/20">
            <flux:text class="font-bold text-red-600 dark:text-red-400">{{ __('app.debit') }}</flux:text>
            <flux:heading size="lg">{{ number_format($s['debit']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-green-50 p-4 dark:bg-green-950/20">
            <flux:text class="font-bold text-green-600 dark:text-green-400">{{ __('app.credit') }}</flux:text>
            <flux:heading size="lg">{{ number_format($s['credit']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-zinc-50 p-4 dark:bg-zinc-900/40">
            <flux:text class="font-bold text-zinc-600 dark:text-zinc-400">{{ __('app.balance') }}</flux:text>
            @php $bal = $s['balance']; @endphp
            <flux:heading size="lg" class="{{ $bal > 0 ? 'text-red-600' : ($bal < 0 ? 'text-green-600' : '') }}">
                @if($bal < 0)
                    ({{ number_format(abs($bal)) }}) {{ __('app.rial') }}
                @else
                    {{ number_format($bal) }} {{ __('app.rial') }}
                @endif
            </flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-blue-50 p-4 dark:bg-blue-950/20">
            <flux:text class="font-bold text-blue-600 dark:text-blue-400">{{ __('app.uncashed_receipts') }}</flux:text>
            <flux:heading size="lg">{{ number_format($s['uncashed_receipts']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-amber-50 p-4 dark:bg-amber-950/20">
            <flux:text class="font-bold text-amber-600 dark:text-amber-400">{{ __('app.uncashed_payments') }}</flux:text>
            <flux:heading size="lg">{{ number_format($s['uncashed_payments']) }} {{ __('app.rial') }}</flux:heading>
        </flux:card>
        <flux:card class="flex flex-col gap-1 bg-violet-50 p-4 dark:bg-violet-950/20">
            <flux:text class="font-bold text-violet-600 dark:text-violet-400">{{ __('app.final_balance') }}</flux:text>
            @php $fb = $s['final_balance']; @endphp
            <flux:heading size="lg" class="{{ $fb > 0 ? 'text-red-600' : ($fb < 0 ? 'text-green-600' : '') }}">
                @if($fb < 0)
                    ({{ number_format(abs($fb)) }}) {{ __('app.rial') }}
                @else
                    {{ number_format($fb) }} {{ __('app.rial') }}
                @endif
            </flux:heading>
        </flux:card>
    </div>

    <flux:separator variant="subtle" />

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.party_sale_invoices_section') }}</flux:heading>
        <flux:table :paginate="$this->partyInvoices">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            @foreach ($this->partyInvoices as $invoice)
                <flux:table.row :key="$invoice->InvoiceId">
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <flux:tooltip content="{{ __('app.view') }}">
                            <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline"
                                         wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: '{{ $invoice->InvoiceId }}' })" />
                        </flux:tooltip>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $invoice->Number }}</flux:table.cell>
                    <flux:table.cell>{{ $invoice->CustomerRealName }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($invoice->Price) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $invoice->Date ? \Morilog\Jalali\Jalalian::fromDateTime($invoice->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.party_inventory_receipts_section') }}</flux:heading>
        <flux:table :paginate="$this->partyInventoryReceipts">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.name') }}</flux:table.column>
                <flux:table.column>{{ __('app.price') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            @foreach ($this->partyInventoryReceipts as $receipt)
                <flux:table.row :key="$receipt->InventoryReceiptID">
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <flux:tooltip content="{{ __('app.view') }}">
                            <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline"
                                         wire:click="$dispatch('panels.accounting.inventory-receipt.view.assign-data', { id: '{{ $receipt->InventoryReceiptID }}' })" />
                        </flux:tooltip>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $receipt->Number }}</flux:table.cell>
                    <flux:table.cell>{{ $receipt->dl->Title ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($receipt->TotalPrice) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $receipt->Date ? \Morilog\Jalali\Jalalian::fromDateTime($receipt->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.party_voucher_lines_section') }}</flux:heading>
        <flux:table :paginate="$this->voucherLines">
            <flux:table.columns>
                <flux:table.column>{{ __('app.voucher_number') }}</flux:table.column>
                <flux:table.column>{{ __('app.voucher_ref') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
                <flux:table.column>{{ __('app.voucher_description') }}</flux:table.column>
                <flux:table.column>{{ __('app.debit') }}</flux:table.column>
                <flux:table.column>{{ __('app.credit') }}</flux:table.column>
            </flux:table.columns>
            @foreach ($this->voucherLines as $line)
                <flux:table.row :key="$line->VoucherItemId">
                    <flux:table.cell class="whitespace-nowrap">{{ $line->voucher_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-mono">{{ $line->VoucherRef }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $line->voucher_date ? \Morilog\Jalali\Jalalian::fromDateTime($line->voucher_date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                    <flux:table.cell class="max-w-md">
                        <flux:text class="line-clamp-2 whitespace-normal text-zinc-700 dark:text-zinc-300">{{ $line->voucher_description ?: '—' }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ number_format((float) ($line->Debit ?? 0)) }}</flux:table.cell>
                    <flux:table.cell>{{ number_format((float) ($line->Credit ?? 0)) }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.party_receipt_cheques_section') }}</flux:heading>
        <flux:table :paginate="$this->partyReceiptCheques">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.amount') }}</flux:table.column>
                <flux:table.column>{{ __('app.cheque_state') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            @foreach ($this->partyReceiptCheques as $cheque)
                <flux:table.row :key="$cheque->ReceiptChequeId">
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <flux:tooltip content="{{ __('app.view') }}">
                            <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline"
                                         wire:click="$dispatch('panels.accounting.receipt-cheque.view.assign-data', { id: '{{ $cheque->ReceiptChequeId }}' })" />
                        </flux:tooltip>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $cheque->Number }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($cheque->Amount) }}</flux:table.cell>
                    <flux:table.cell>{{ $this->receiptChequeStateLabel((int) $cheque->State) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <div>
        <flux:heading size="lg" class="mb-3">{{ __('app.party_payment_cheques_section') }}</flux:heading>
        <flux:table :paginate="$this->partyPaymentCheques">
            <flux:table.columns>
                <flux:table.column class="w-1 whitespace-nowrap">{{ __('app.options') }}</flux:table.column>
                <flux:table.column>{{ __('app.number') }}</flux:table.column>
                <flux:table.column>{{ __('app.amount') }}</flux:table.column>
                <flux:table.column>{{ __('app.cheque_state') }}</flux:table.column>
                <flux:table.column>{{ __('app.date') }}</flux:table.column>
            </flux:table.columns>
            @foreach ($this->partyPaymentCheques as $cheque)
                <flux:table.row :key="$cheque->PaymentChequeId">
                    <flux:table.cell class="w-1 whitespace-nowrap">
                        <flux:tooltip content="{{ __('app.view') }}">
                            <flux:button size="xs" variant="primary" color="sky" icon="eye" icon:variant="outline"
                                         wire:click="$dispatch('panels.accounting.payment-cheque.view.assign-data', { id: '{{ $cheque->PaymentChequeId }}' })" />
                        </flux:tooltip>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $cheque->Number }}</flux:table.cell>
                    <flux:table.cell>{{ number_format($cheque->Amount) }}</flux:table.cell>
                    <flux:table.cell>{{ $this->paymentChequeStateLabel((int) $cheque->State) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $cheque->Date ? \Morilog\Jalali\Jalalian::fromDateTime($cheque->Date)->format('%Y-%m-%d') : '-' }}
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    </div>

    <livewire:panels.accounting.invoice.view />
    <livewire:panels.accounting.inventory-receipt.view />
    <livewire:panels.accounting.receipt-cheque.view />
    <livewire:panels.accounting.payment-cheque.view />
</div>
