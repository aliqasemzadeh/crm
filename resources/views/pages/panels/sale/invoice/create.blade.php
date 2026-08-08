<?php

use App\Livewire\Forms\Accounting\InvoiceForm;
use App\Livewire\Panels\Accounting\Invoice\Concerns\HandlesInvoiceForm;
use App\Models\Sepidar\INV\Item;
use App\Services\Sale\SaleTemporaryInvoice;
use App\Services\Sepidar\InvoiceCreator;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.sale')] class extends Component
{
    use HandlesInvoiceForm;

    public InvoiceForm $form;

    protected function invoiceUiPrefix(): string
    {
        return 'panels.sale.invoice';
    }

    protected function emptyFeeWhenMissing(): bool
    {
        return true;
    }

    protected function feeValidationRules(): array
    {
        return ['required', 'numeric', 'gt:0'];
    }

    public function mount(SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $this->form->date = Jalalian::now()->format('Y/m/d');
        $this->form->sale_type_ref = 2;
        $this->items = $this->itemsFromTemporaryInvoice($draft);
    }

    public function applySitePrices(): void
    {
        $this->authorize('sales_invoice_create');

        foreach ($this->items as $index => $row) {
            $itemId = (int) ($row['item_ref'] ?? 0);

            if ($itemId <= 0) {
                continue;
            }

            $sitePrice = Item::query()->find($itemId)?->siteMinPriceRial();
            $this->items[$index]['fee'] = $sitePrice !== null && $sitePrice > 0
                ? (int) $sitePrice
                : '';
            $this->recalculateLineTax($index);
        }

        Flux::toast(__('app.site_prices_applied'));
    }

    public function save(InvoiceCreator $creator, SaleTemporaryInvoice $draft): void
    {
        $this->authorize('sales_invoice_create');

        $this->validateInvoice();

        try {
            $invoice = $creator->create($this->formPayload());
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.invoice_create_failed'), variant: 'danger');

            return;
        }

        $draft->clear();
        $this->dispatch('panels.sale.temporary-invoice.updated');

        Flux::toast(__('app.invoice_created', ['number' => $invoice->Number]));

        $this->redirect(route('panels.sale.invoice.view', $invoice->InvoiceId), navigate: true);
    }

    /**
     * @return list<array{row_id: string, item_ref: int|null, quantity: float|int, fee: float|int|string, discount: int, tax: int, description: string}>
     */
    protected function itemsFromTemporaryInvoice(SaleTemporaryInvoice $draft): array
    {
        $quantities = $draft->all();

        if ($quantities === []) {
            return [
                $this->emptyRow(),
            ];
        }

        $items = Item::query()
            ->whereIn('ItemID', array_keys($quantities))
            ->get()
            ->keyBy('ItemID');

        $rows = [];

        foreach ($quantities as $itemId => $quantity) {
            if (! $items->has($itemId)) {
                continue;
            }

            $fee = (float) $items->get($itemId)->getLastSalePrice();

            $rows[] = [
                'row_id' => (string) Str::uuid(),
                'item_ref' => $itemId,
                'quantity' => $quantity,
                'fee' => $this->normalizeDefaultFee($fee),
                'discount' => 0,
                'tax' => 0,
                'description' => '',
            ];
        }

        if ($rows === []) {
            return [
                $this->emptyRow(),
            ];
        }

        $this->items = array_values($rows);
        $this->recalculateLineTaxes();

        return $this->items;
    }
};
?>

<x-slot name="title">
    {{ __('app.create_invoice') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.create_invoice') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoice_create_description') }}</flux:subheading>
            </div>

            <flux:button variant="ghost" href="{{ route('panels.sale.invoice.index') }}" wire:navigate icon="arrow-right">
                {{ __('app.back') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" />
    </div>

    <flux:callout variant="warning" icon="triangle-alert" class="mb-4">
        {{ __('app.invoice_price_attention_warning') }}
    </flux:callout>

    <div class="mb-6">
        <flux:button
            variant="primary"
            color="rose"
            icon="globe-alt"
            class="w-full sm:w-auto"
            wire:click="applySitePrices"
        >
            {{ __('app.apply_site_prices') }}
        </flux:button>
    </div>

    <form wire:submit="save">
        @include('pages.panels.accounting.invoice._form', [
            'heading' => __('app.sales_invoice'),
            'subheading' => __('app.invoice_create_description'),
            'invoiceUiPrefix' => 'panels.sale.invoice',
            'invoiceRoutePrefix' => 'panels.sale.invoice',
            'partyCreatePermissions' => ['sales_party_create', 'sales_invoice_create'],
        ])
    </form>

    @include('pages.panels.accounting.invoice._modals', [
        'invoiceUiPrefix' => 'panels.sale.invoice',
    ])
</div>
