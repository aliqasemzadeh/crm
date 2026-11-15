<?php

use App\Enums\InvoiceReviewStatusEnum;
use App\Models\Crm\InvoiceReview;
use App\Models\Sepidar\SLS\Invoice;
use App\Services\Sepidar\InventoryDeliveryService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts::panels.warehouse')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('warehouse_invoice_delivery_index');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function reviews()
    {
        return InvoiceReview::query()
            ->with(['invoice.creator'])
            ->where('status', InvoiceReviewStatusEnum::APPROVED)
            ->where('warehouse_status', 'pending_delivery')
            ->when(trim($this->search) !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('accounting_reviewed_at')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function deliver(int $reviewId, InventoryDeliveryService $service): void
    {
        $this->authorize('warehouse_invoice_delivery_create');

        $review = InvoiceReview::query()
            ->whereKey($reviewId)
            ->where('status', InvoiceReviewStatusEnum::APPROVED)
            ->where('warehouse_status', 'pending_delivery')
            ->first();

        if (! $review) {
            Flux::toast(__('app.warehouse_invoice_delivery_unavailable'), variant: 'warning');

            return;
        }

        $invoice = Invoice::query()->with(['items', 'customer'])->find($review->sepidar_invoice_id);

        if (! $invoice) {
            Flux::toast(__('app.warehouse_invoice_delivery_invoice_missing'), variant: 'danger');

            return;
        }

        try {
            $deliveries = $service->createFromInvoice($invoice);

            $review->update([
                'warehouse_status' => 'delivered',
                'warehouse_handled_by' => auth()->id(),
                'warehouse_handled_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.warehouse_invoice_delivery_failed'), variant: 'danger');

            return;
        }

        $numbers = $deliveries->pluck('Number')->filter()->implode(', ');

        unset($this->reviews);
        Flux::toast(__('app.warehouse_invoice_delivery_created', [
            'number' => $numbers !== '' ? $numbers : '-',
        ]));
    }

    public function formatDate(mixed $date): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d');
    }
};
?>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.warehouse_invoice_deliveries') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.warehouse_invoice_deliveries_description') }}</flux:subheading>
            </div>
        </div>
        <flux:separator variant="subtle" />
    </div>

    <flux:table :paginate="$this->reviews">
        <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
            <flux:table.column colspan="6" class="bg-white dark:bg-zinc-900">
                <div class="flex flex-col gap-1 pe-2 items-end">
                    <flux:input
                        size="sm"
                        placeholder="{{ __('app.search_placeholder') }}"
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                    />
                </div>
            </flux:table.column>
        </flux:table.columns>
        <flux:table.columns>
            <flux:table.column>{{ __('app.invoice_number') }}</flux:table.column>
            <flux:table.column>{{ __('app.customer') }}</flux:table.column>
            <flux:table.column>{{ __('app.date') }}</flux:table.column>
            <flux:table.column>{{ __('app.net_amount') }}</flux:table.column>
            <flux:table.column>{{ __('app.invoice_review_reviewed_at') }}</flux:table.column>
            <flux:table.column>{{ __('app.options') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->reviews as $review)
                <flux:table.row :key="$review->id">
                    <flux:table.cell>{{ $review->invoice_number ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $review->customer_name ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatDate($review->invoice_date) }}</flux:table.cell>
                    <flux:table.cell class="tabular-nums">{{ number_format((float) ($review->invoice_net_price ?? 0)) }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $review->accounting_reviewed_at
                            ? Jalalian::fromDateTime($review->accounting_reviewed_at)->format('Y/m/d H:i')
                            : '—' }}
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            @can('warehouse_invoice_delivery_view')
                                <flux:tooltip content="{{ __('app.view') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="teal"
                                        icon="eye"
                                        icon:variant="outline"
                                        href="{{ route('panels.warehouse.invoice-delivery.view', $review->sepidar_invoice_id) }}"
                                        wire:navigate
                                    />
                                </flux:tooltip>
                            @endcan
                            @can('warehouse_invoice_delivery_create')
                                <flux:tooltip content="{{ __('app.create_warehouse_delivery') }}">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        color="orange"
                                        icon="truck"
                                        icon:variant="outline"
                                        wire:click="deliver({{ $review->id }})"
                                        wire:confirm="{{ __('common.are_you_sure') }}"
                                    />
                                </flux:tooltip>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        {{ __('app.no_results') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
