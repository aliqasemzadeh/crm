<?php

namespace App\Livewire\Panels\Customer\Invoice;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\SLS\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panels.customer')]
class History extends Component
{
    use WithPagination;

    public int|string $partyId;

    public ?Party $customer = null;

    public string $search = '';

    public function mount($partyId): void
    {
        $this->partyId = $partyId;
        $this->customer = Party::query()->findOrFail($partyId);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function customerName(): string
    {
        if (! $this->customer) {
            return '-';
        }

        return trim(implode(' ', array_filter([
            trim((string) ($this->customer->Name ?? '')),
            trim((string) ($this->customer->LastName ?? '')),
        ], static fn (string $part): bool => $part !== ''))) ?: '-';
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = Invoice::query()->where('CustomerPartyRef', $this->partyId);

        return [
            'total_count' => (clone $baseQuery)->count(),
            'total_amount' => (clone $baseQuery)->sum('Price') ?? 0,
            'last_invoice' => (clone $baseQuery)->orderByDesc('Date')->orderByDesc('InvoiceId')->first(),
        ];
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with(['items'])
            ->where('CustomerPartyRef', $this->partyId)
            ->when(filled($this->search), function ($query) {
                $query->where(function ($q) {
                    $q->where('Number', 'like', "%{$this->search}%")
                        ->orWhere('Description', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('Date')
            ->orderByDesc('InvoiceId')
            ->paginate(config('general.per_page', 15));
    }

    public function render()
    {
        return view('livewire.panels.customer.invoice.history')
            ->layout('layouts.panels.customer');
    }
}
