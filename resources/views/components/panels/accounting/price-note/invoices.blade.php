<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $groupingId;

    #[On('panels.accounting.price-note.invoices.assign-data')]
    public function assignData($groupingId)
    {
        $this->groupingId = $groupingId;
        $this->resetPage();
        Flux::modal('panels.accounting.price-note.invoices.modal')->show();
    }

    #[Computed]
    public function invoices()
    {
        $grouping = Grouping::find($this->groupingId);
        if (!$grouping) {
            return collect();
        }

        $groupIds = $grouping->getAllChildrenIds();
        $fiscalYearRef = config('sepidar.FiscalYearRef');

        return Invoice::query()
            ->where('FiscalYearRef', $fiscalYearRef)
            ->whereHas('items.item', function ($query) use ($groupIds) {
                $query->whereIn('CodingGroupRef', $groupIds);
            })
            ->with(['customer'])
            ->latest('Number')
            ->paginate(10);
    }
};
?>

<div>
    <flux:modal name="panels.accounting.price-note.invoices.modal" flyout position="right" class="md:w-1/2">
        <div class="space-y-6">
            <div>
                <flux:heading size="xl">{{ __('app.sales_invoices') }}</flux:heading>
                <flux:subheading>{{ __('app.list_of_invoices_for_selected_group') }}</flux:subheading>
            </div>

            <flux:table :paginate="$this->invoices">
                <flux:table.columns>
                    <flux:table.column>{{ __('app.invoice_number') }}</flux:table.column>
                    <flux:table.column>{{ __('app.customer') }}</flux:table.column>
                    <flux:table.column>{{ __('app.date') }}</flux:table.column>
                    <flux:table.column align="end"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->invoices as $invoice)
                        <flux:table.row :key="$invoice->InvoiceId">
                            <flux:table.cell font="medium">{{ $invoice->Number }}</flux:table.cell>
                            <flux:table.cell>{{ $invoice->customer?->Title ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $invoice->Date }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:tooltip content="{{ __('app.view_invoice') }}">
                                    <flux:button size="xs" variant="primary" color="sky" icon="eye"
                                        wire:click="$dispatch('panels.accounting.invoice.view.assign-data', { InvoiceId: {{ $invoice->InvoiceId }} })" />
                                </flux:tooltip>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>
</div>
