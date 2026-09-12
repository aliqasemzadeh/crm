<?php

use App\Enums\InvoiceReviewStatusEnum;
use App\Jobs\Notification\NotifyWarehouseOfApprovedInvoiceReviewJob;
use App\Livewire\Forms\Accounting\InvoiceReviewDecisionForm;
use App\Models\Crm\InvoiceReview;
use App\Models\Sepidar\SLS\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    public Invoice $invoice;

    public InvoiceReviewDecisionForm $decisionForm;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('accounting_invoice_view');

        $this->invoice = $invoice->load(['items.item.image', 'creator', 'modifier', 'customer']);
    }

    #[Computed]
    public function review(): ?InvoiceReview
    {
        return InvoiceReview::query()
            ->with('accountingReviewer')
            ->where('sepidar_invoice_id', $this->invoice->InvoiceId)
            ->first();
    }

    public function partyName(): string
    {
        $party = $this->invoice->customer;

        if (! $party) {
            return (string) ($this->invoice->CustomerRealName ?? '-');
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== ''))) ?: (string) ($this->invoice->CustomerRealName ?? '-');
    }

    public function openDecision(string $decision): void
    {
        if ($decision === InvoiceReviewStatusEnum::APPROVED->value) {
            $this->authorize('accounting_invoice_review_approve');
        } else {
            $this->authorize('accounting_invoice_review_reject');
        }

        $review = $this->review;

        if (! $review || ! $review->isPending()) {
            Flux::toast(text: __('app.invoice_review_already_reviewed'), variant: 'warning');

            return;
        }

        $this->decisionForm->decision = $decision;
        $this->decisionForm->note = '';

        Flux::modal('panels.accounting.invoice.review.decision')->show();
    }

    public function saveDecision(): void
    {
        $this->decisionForm->validate();

        if ($this->decisionForm->decision === InvoiceReviewStatusEnum::APPROVED->value) {
            $this->authorize('accounting_invoice_review_approve');
        } else {
            $this->authorize('accounting_invoice_review_reject');
        }

        $review = $this->review;

        if (! $review) {
            Flux::toast(text: __('app.invoice_review_unavailable'), variant: 'danger');

            return;
        }

        $status = InvoiceReviewStatusEnum::from($this->decisionForm->decision);
        $updated = false;

        DB::transaction(function () use ($review, $status, &$updated): void {
            $payload = [
                'status' => $status->value,
                'accounting_reviewed_by' => auth()->id(),
                'accounting_reviewed_at' => now(),
                'accounting_note' => $this->decisionForm->note ?: null,
            ];

            if ($status === InvoiceReviewStatusEnum::APPROVED) {
                $payload['warehouse_status'] = 'pending_delivery';
            }

            $affected = InvoiceReview::query()
                ->whereKey($review->id)
                ->where('status', InvoiceReviewStatusEnum::PENDING->value)
                ->update($payload);

            $updated = $affected > 0;
        });

        if (! $updated) {
            unset($this->review);
            Flux::toast(text: __('app.invoice_review_already_reviewed'), variant: 'warning');
            Flux::modal('panels.accounting.invoice.review.decision')->close();

            return;
        }

        if ($status === InvoiceReviewStatusEnum::APPROVED) {
            NotifyWarehouseOfApprovedInvoiceReviewJob::dispatch($review->id);
            Flux::toast(text: __('app.invoice_review_approved_toast'), variant: 'success');
        } else {
            Flux::toast(text: __('app.invoice_review_rejected_toast'), variant: 'success');
        }

        unset($this->review);
        Flux::modal('panels.accounting.invoice.review.decision')->close();
    }
};
?>

<x-slot name="title">
    {{ __('app.invoice_details') }} #{{ $invoice->Number }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.invoice_details') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($this->review?->isPending())
                    @can('accounting_invoice_review_approve')
                        <flux:tooltip content="{{ __('app.invoice_review_approve') }}">
                            <flux:button
                                size="sm"
                                variant="primary"
                                color="emerald"
                                icon="check"
                                icon:variant="outline"
                                wire:click="openDecision('{{ \App\Enums\InvoiceReviewStatusEnum::APPROVED->value }}')"
                            />
                        </flux:tooltip>
                    @endcan
                    @can('accounting_invoice_review_reject')
                        <flux:tooltip content="{{ __('app.invoice_review_reject') }}">
                            <flux:button
                                size="sm"
                                variant="primary"
                                color="red"
                                icon="x"
                                icon:variant="outline"
                                wire:click="openDecision('{{ \App\Enums\InvoiceReviewStatusEnum::REJECTED->value }}')"
                            />
                        </flux:tooltip>
                    @endcan
                @endif
                @can('accounting_invoice_edit')
                    <flux:button
                        variant="primary"
                        color="orange"
                        icon="pencil"
                        href="{{ route('panels.accounting.invoice.edit', $invoice->InvoiceId) }}"
                        wire:navigate
                    >
                        {{ __('app.edit') }}
                    </flux:button>
                @endcan
                <flux:button
                    variant="primary"
                    color="sky"
                    icon="printer"
                    href="{{ route('panels.accounting.invoice.print', $invoice->InvoiceId) }}"
                    wire:navigate
                >
                    {{ __('app.print') }}
                </flux:button>
                <flux:button variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                    {{ __('app.back') }}
                </flux:button>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <div class="mx-auto max-w-5xl space-y-6">
        @if ($this->review)
            <flux:card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <flux:heading size="lg">{{ __('app.invoice_review') }}</flux:heading>
                        <div class="mt-2">
                            <flux:badge color="{{ $this->review->status->color() }}" size="sm">
                                {{ $this->review->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                    @if (! $this->review->isPending())
                        <div class="text-sm space-y-1">
                            <div class="flex gap-2 justify-between">
                                <span class="text-zinc-500">{{ __('app.invoice_review_reviewer') }}:</span>
                                <span class="font-medium">{{ $this->review->accountingReviewer?->name ?? '-' }}</span>
                            </div>
                            <div class="flex gap-2 justify-between">
                                <span class="text-zinc-500">{{ __('app.invoice_review_reviewed_at') }}:</span>
                                <span class="font-medium">
                                    {{ $this->review->accounting_reviewed_at ? Jalalian::fromDateTime($this->review->accounting_reviewed_at)->format('Y/m/d H:i') : '-' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                @if (filled($this->review->accounting_note))
                    <div class="mt-4">
                        <flux:text size="sm">{{ __('app.invoice_review_note') }}</flux:text>
                        <flux:heading size="sm">{{ $this->review->accounting_note }}</flux:heading>
                    </div>
                @endif

                @php
                    $balance = $this->review->balance_snapshot ?? [];
                    $finalBalance = (float) ($balance['final_balance'] ?? 0);
                    $direction = match (true) {
                        $finalBalance > 0 => __('app.debtor'),
                        $finalBalance < 0 => __('app.creditor'),
                        default => __('app.settled'),
                    };
                @endphp

                <div class="mt-6">
                    <flux:heading size="sm" class="mb-3">{{ __('app.invoice_review_balance_snapshot') }}</flux:heading>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <flux:text size="sm">{{ __('app.final_balance') }}</flux:text>
                            <flux:heading size="sm" class="tabular-nums">{{ number_format(abs($finalBalance)) }} ({{ $direction }})</flux:heading>
                        </div>
                        <div>
                            <flux:text size="sm">{{ __('app.debit') }}</flux:text>
                            <flux:heading size="sm" class="tabular-nums">{{ number_format((float) ($balance['debit'] ?? 0)) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text size="sm">{{ __('app.credit') }}</flux:text>
                            <flux:heading size="sm" class="tabular-nums">{{ number_format((float) ($balance['credit'] ?? 0)) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text size="sm">{{ __('app.uncashed_receipts') ?? __('app.receipt_cheques') }}</flux:text>
                            <flux:heading size="sm" class="tabular-nums">{{ number_format((float) ($balance['uncashed_receipts'] ?? 0)) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text size="sm">{{ __('app.uncashed_payments') ?? __('app.payment_cheques') }}</flux:text>
                            <flux:heading size="sm" class="tabular-nums">{{ number_format((float) ($balance['uncashed_payments'] ?? 0)) }}</flux:heading>
                        </div>
                        <div>
                            <flux:text size="sm">{{ __('app.date') }}</flux:text>
                            <flux:heading size="sm">
                                {{ $this->review->snapshot_at ? Jalalian::fromDateTime($this->review->snapshot_at)->format('Y/m/d H:i') : '-' }}
                            </flux:heading>
                        </div>
                    </div>
                </div>

                @if (! empty($this->review->stock_snapshot))
                    <div class="mt-6">
                        <flux:heading size="sm" class="mb-3">{{ __('app.invoice_review_stock_snapshot') }}</flux:heading>
                        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full min-w-[560px] text-sm">
                                <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    <tr>
                                        <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                                        <th class="px-3 py-2 text-right font-medium">{{ __('app.quantity') }}</th>
                                        <th class="px-3 py-2 text-right font-medium">{{ __('app.invoice_review_stock_quantity') }}</th>
                                        <th class="px-3 py-2 text-right font-medium">{{ __('app.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($this->review->stock_snapshot as $row)
                                        @php
                                            $hint = match (true) {
                                                ! empty($row['is_zero']) => ['label' => __('app.invoice_review_stock_zero'), 'color' => 'red'],
                                                ! empty($row['is_short']) => ['label' => __('app.invoice_review_stock_short'), 'color' => 'amber'],
                                                default => ['label' => __('app.invoice_review_stock_sufficient'), 'color' => 'emerald'],
                                            };
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-3">
                                                <div class="font-medium">{{ $row['title'] ?? '-' }}</div>
                                                <div class="text-xs text-zinc-500">{{ $row['code'] ?? '' }}</div>
                                            </td>
                                            <td class="px-3 py-3 tabular-nums">{{ number_format((float) ($row['invoice_quantity'] ?? 0)) }}</td>
                                            <td class="px-3 py-3 tabular-nums">{{ number_format((float) ($row['stock_quantity'] ?? 0)) }}</td>
                                            <td class="px-3 py-3">
                                                <flux:badge color="{{ $hint['color'] }}" size="sm">{{ $hint['label'] }}</flux:badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </flux:card>
        @else
            <flux:callout icon="info">
                <flux:callout.heading>{{ __('app.invoice_review') }}</flux:callout.heading>
                <flux:callout.text>{{ __('app.invoice_review_status_not_registered') }}</flux:callout.text>
            </flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-300 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-5 dark:border-zinc-700 dark:bg-zinc-800/60">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('app.sales_invoice') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('app.invoice_number') }}: {{ $invoice->Number }}</flux:text>
                    </div>
                    <div class="text-sm space-y-1">
                        <div class="flex gap-2 justify-between">
                            <span class="text-zinc-500">{{ __('app.issuer') }}:</span>
                            <span class="font-medium">{{ $invoice->creator?->Name ?? '-' }}</span>
                        </div>
                        <div class="flex gap-2 justify-between">
                            <span class="text-zinc-500">{{ __('app.date') }}:</span>
                            <span class="font-medium">
                                {{ $invoice->Date ? Jalalian::fromDateTime($invoice->Date)->format('Y/m/d') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <flux:text size="sm">{{ __('app.customer') }}</flux:text>
                        <flux:heading size="sm">{{ $this->partyName() }}</flux:heading>
                    </div>
                    <div>
                        <flux:text size="sm">{{ __('app.sale_type') }}</flux:text>
                        <flux:heading size="sm">
                            {{ (int) $invoice->SaleTypeRef === 1 ? __('app.official') : __('app.unofficial') }}
                        </flux:heading>
                    </div>
                    <div>
                        <flux:text size="sm">{{ __('app.last_modifier') }}</flux:text>
                        <flux:heading size="sm">{{ $invoice->modifier?->Name ?? '-' }}</flux:heading>
                    </div>
                    @if (filled($invoice->Description))
                        <div class="md:col-span-3">
                            <flux:text size="sm">{{ __('app.description') }}</flux:text>
                            <flux:heading size="sm">{{ $invoice->Description }}</flux:heading>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            <tr>
                                <th class="px-3 py-2 text-right font-medium w-10">#</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.item') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.quantity') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.fee') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.discount') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.tax') }}</th>
                                <th class="px-3 py-2 text-right font-medium">{{ __('app.line_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($invoice->items as $index => $item)
                                <tr>
                                    <td class="px-3 py-3 text-zinc-500">{{ $index + 1 }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($item->item?->image?->Thumbnail)
                                                <img
                                                    src="data:image/jpeg;base64,{{ base64_encode($item->item->image->Thumbnail) }}"
                                                    alt=""
                                                    class="size-12 rounded-md object-cover shadow-sm"
                                                >
                                            @else
                                                <div class="flex size-12 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                                                    <flux:icon name="package" class="size-5 text-zinc-400" />
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-medium">{{ $item->item?->Title ?? '-' }}</div>
                                                <div class="text-xs text-zinc-500">{{ $item->item?->Code }}</div>
                                                @if (filled($item->Description))
                                                    <div class="text-xs text-zinc-500 mt-1">{{ $item->Description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 tabular-nums">{{ number_format($item->Quantity) }}</td>
                                    <td class="px-3 py-3 tabular-nums">{{ number_format($item->Fee) }}</td>
                                    <td class="px-3 py-3 tabular-nums">{{ number_format($item->Discount) }}</td>
                                    <td class="px-3 py-3 tabular-nums">{{ number_format($item->Tax) }}</td>
                                    <td class="px-3 py-3 font-semibold tabular-nums">{{ number_format($item->NetPrice) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="ms-auto w-full max-w-sm space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.price') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($invoice->Price) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.discount') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($invoice->Discount) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('app.tax') }}</span>
                        <span class="font-medium tabular-nums">{{ number_format($invoice->Tax) }}</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ __('app.net_amount') }}</flux:heading>
                        <flux:heading size="lg" class="tabular-nums">{{ number_format($invoice->NetPrice) }}</flux:heading>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <flux:modal name="panels.accounting.invoice.review.decision" flyout position="right" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $decisionForm->decision === \App\Enums\InvoiceReviewStatusEnum::APPROVED->value
                        ? __('app.invoice_review_approve')
                        : __('app.invoice_review_reject') }}
                </flux:heading>
            </div>

            <flux:field>
                <flux:label>{{ __('app.invoice_review_note') }}</flux:label>
                <flux:textarea
                    wire:model="decisionForm.note"
                    rows="4"
                    placeholder="{{ __('app.invoice_review_note_placeholder') }}"
                />
                <flux:error name="decisionForm.note" />
            </flux:field>

            <flux:button
                variant="primary"
                color="{{ $decisionForm->decision === \App\Enums\InvoiceReviewStatusEnum::APPROVED->value ? 'emerald' : 'red' }}"
                class="w-full"
                wire:click="saveDecision"
            >
                {{ __('app.invoice_review_save_decision') }}
            </flux:button>
        </div>
    </flux:modal>
</div>
