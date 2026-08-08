<?php

use App\Livewire\Forms\Accounting\InvoiceForm;
use App\Livewire\Panels\Accounting\Invoice\Concerns\HandlesInvoiceForm;
use App\Services\Sepidar\InvoiceCreator;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

new #[Layout('layouts.panels.accounting')] class extends Component
{
    use HandlesInvoiceForm;

    public InvoiceForm $form;

    public function mount(): void
    {
        $this->authorize('accounting_invoice_create');

        $this->form->date = Jalalian::now()->format('Y/m/d');
        $this->form->sale_type_ref = 2;
        $this->ensureInvoiceFormDefaults();
        $this->items = [
            $this->emptyRow(),
        ];
    }

    public function save(InvoiceCreator $creator): void
    {
        $this->authorize('accounting_invoice_create');

        $this->validateInvoice();

        try {
            $invoice = $creator->create($this->formPayload());
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.invoice_create_failed'), variant: 'danger');

            return;
        }

        Flux::toast(__('app.invoice_created', ['number' => $invoice->Number]));

        $this->redirect(route('panels.accounting.invoice.view', $invoice->InvoiceId), navigate: true);
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

            <flux:button variant="ghost" href="{{ route('panels.accounting.invoice.index') }}" wire:navigate icon="arrow-right">
                {{ __('app.back') }}
            </flux:button>
        </div>

        <flux:separator variant="subtle" />
    </div>

    @include('pages.panels.accounting.invoice._form', [
        'heading' => __('app.sales_invoice'),
        'subheading' => __('app.invoice_create_description'),
    ])

    @include('pages.panels.accounting.invoice._modals')
</div>
