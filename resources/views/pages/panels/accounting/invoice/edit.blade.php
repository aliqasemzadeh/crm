<?php

use App\Livewire\Forms\Accounting\InvoiceForm;
use App\Livewire\Panels\Accounting\Invoice\Concerns\HandlesInvoiceForm;
use App\Models\Sepidar\SLS\Invoice;
use App\Services\Sepidar\InvoiceUpdater;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use HandlesInvoiceForm;

    public InvoiceForm $form;

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('accounting_invoice_edit');

        $this->invoice = $invoice->load(['items']);

        $this->form->customer_party_ref = (int) $this->invoice->CustomerPartyRef;
        $this->form->sale_type_ref = (int) $this->invoice->SaleTypeRef;
        $this->form->date = $this->invoice->Date
            ? Jalalian::fromDateTime($this->invoice->Date)->format('Y/m/d')
            : Jalalian::now()->format('Y/m/d');
        $this->form->description = (string) ($this->invoice->Description ?? '');
        $this->items = $this->invoice->items->map(function ($item) {
            return [
                'row_id' => (string) \Illuminate\Support\Str::uuid(),
                'item_ref' => (int) $item->ItemRef,
                'quantity' => $item->Quantity,
                'fee' => (int) $item->Fee,
                'discount' => (int) ($item->Discount ?? 0),
                'tax' => (int) ($item->Tax ?? 0),
                'description' => (string) ($item->Description ?? ''),
            ];
        })->values()->all();

        if ($this->items === []) {
            $this->items = [$this->emptyRow()];
        }
    }

    public function save(InvoiceUpdater $updater): void
    {
        $this->authorize('accounting_invoice_edit');

        $this->validateInvoice();

        try {
            $invoice = $updater->update($this->invoice, $this->formPayload());
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.invoice_update_failed'), variant: 'danger');

            return;
        }

        Flux::toast(__('app.invoice_updated', ['number' => $invoice->Number]));

        $this->redirect(route('panels.accounting.invoice.view', $invoice->InvoiceId), navigate: true);
    }
};
?>

<x-slot name="title">
    {{ __('app.edit_invoice') }}
</x-slot>

<div>
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('app.edit_invoice') }}</flux:heading>
                <flux:subheading size="lg" class="mb-6">{{ __('app.invoice_edit_description') }}</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <flux:button
                    variant="ghost"
                    href="{{ route('panels.accounting.invoice.print', $invoice->InvoiceId) }}"
                    wire:navigate
                    icon="printer"
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

    <form wire:submit="save">
        @include('pages.panels.accounting.invoice._form', [
            'heading' => __('app.sales_invoice'),
            'subheading' => __('app.invoice_edit_description'),
            'invoiceNumber' => $invoice->Number,
        ])
    </form>

    @include('pages.panels.accounting.invoice._modals')
</div>
