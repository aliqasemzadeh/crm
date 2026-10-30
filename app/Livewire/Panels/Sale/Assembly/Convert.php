<?php

namespace App\Livewire\Panels\Sale\Assembly;

use App\Ai\Agents\AssemblyOrderAssistant;
use App\Models\Sepidar\SLS\Invoice;
use App\Models\ServiceCenter\AssemblyOrder;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Convert extends Component
{
    public ?int $invoiceId = null;

    public string $invoiceNumber = '';

    public string $customerName = '';

    public int $quantity = 1;

    public string $notes = '';

    public string $aiPrompt = '';

    public bool $isGeneratingAi = false;

    /** @var array<int, array{title: string, sepidar_item_id: int|null, sepidar_invoice_item_id: int|null, quantity_per_unit: int, requires_serial: bool, notes: string}> */
    public array $parts = [];

    #[On('panels.sale.assembly.convert.assign-data')]
    public function assignData(int $invoiceId): void
    {
        $this->authorize('sales_assembly_convert');

        $invoice = Invoice::query()
            ->with(['items.item', 'customer'])
            ->findOrFail($invoiceId);

        if (AssemblyOrder::hasActiveForInvoice((int) $invoice->InvoiceId)) {
            Flux::toast(__('app.assembly_order_already_exists'), variant: 'warning');

            return;
        }

        $this->resetForm();
        $this->invoiceId = (int) $invoice->InvoiceId;
        $this->invoiceNumber = (string) $invoice->Number;

        $party = $invoice->customer;
        $this->customerName = (string) ($invoice->CustomerRealName ?? '');
        if ($party) {
            $name = trim(implode(' ', array_filter([
                trim((string) ($party->Name ?? '')),
                trim((string) ($party->LastName ?? '')),
            ])));
            if ($name !== '') {
                $this->customerName = $name;
            }
        }

        $this->parts = $invoice->items->map(function ($item) {
            return [
                'title' => (string) ($item->item?->Title ?: ($item->Description ?: __('app.item'))),
                'sepidar_item_id' => $item->ItemRef ? (int) $item->ItemRef : null,
                'sepidar_invoice_item_id' => $item->InvoiceItemID ? (int) $item->InvoiceItemID : null,
                'quantity_per_unit' => max(1, (int) $item->Quantity),
                'requires_serial' => true,
                'notes' => (string) ($item->Description ?? ''),
            ];
        })->values()->all();

        $this->quantity = 1;

        Flux::modal('panels.sale.assembly.convert.modal')->show();
    }

    public function addPart(): void
    {
        $this->parts[] = [
            'title' => '',
            'sepidar_item_id' => null,
            'sepidar_invoice_item_id' => null,
            'quantity_per_unit' => 1,
            'requires_serial' => true,
            'notes' => '',
        ];
    }

    public function removePart(int $index): void
    {
        unset($this->parts[$index]);
        $this->parts = array_values($this->parts);
    }

    public function generateWithAi(): void
    {
        $this->authorize('sales_assembly_convert');

        $prompt = trim($this->aiPrompt);
        if ($prompt === '') {
            Flux::toast(__('app.assembly_ai_prompt_required'), variant: 'warning');

            return;
        }

        $this->isGeneratingAi = true;

        try {
            $context = "فاکتور: {$this->invoiceNumber}\nمشتری: {$this->customerName}\nقطعات فعلی:\n";
            foreach ($this->parts as $part) {
                $context .= "- {$part['title']} x{$part['quantity_per_unit']}\n";
            }
            $context .= "\nدرخواست کاربر:\n{$prompt}";

            $response = (new AssemblyOrderAssistant)->prompt(
                $context,
                provider: 'gap',
                model: config('ai.default_model'),
                timeout: 120,
            );

            $qty = (int) ($response['quantity'] ?? 0);
            if ($qty > 0) {
                $this->quantity = $qty;
            }

            $notes = trim((string) ($response['notes'] ?? ''));
            if ($notes !== '') {
                $this->notes = $notes;
            }

            $aiParts = $response['parts'] ?? [];
            if (is_array($aiParts) && count($aiParts) > 0) {
                foreach ($aiParts as $aiPart) {
                    $title = trim((string) ($aiPart['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $this->parts[] = [
                        'title' => $title,
                        'sepidar_item_id' => null,
                        'sepidar_invoice_item_id' => null,
                        'quantity_per_unit' => max(1, (int) ($aiPart['quantity_per_unit'] ?? 1)),
                        'requires_serial' => (bool) ($aiPart['requires_serial'] ?? true),
                        'notes' => (string) ($aiPart['notes'] ?? ''),
                    ];
                }
            }

            Flux::toast(__('app.assembly_ai_filled'));
        } catch (Throwable $e) {
            Log::warning('Assembly order AI failed', ['message' => $e->getMessage()]);
            Flux::toast(__('app.assembly_ai_failed'), variant: 'danger');
        } finally {
            $this->isGeneratingAi = false;
        }
    }

    public function save(): void
    {
        $this->authorize('sales_assembly_convert');

        $this->validate([
            'invoiceId' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.title' => ['required', 'string', 'max:255'],
            'parts.*.quantity_per_unit' => ['required', 'integer', 'min:1', 'max:100'],
            'parts.*.requires_serial' => ['boolean'],
        ], [], [
            'quantity' => __('app.assembly_quantity'),
            'parts' => __('app.assembly_bom_parts'),
            'parts.*.title' => __('app.assembly_part_title'),
        ]);

        if (AssemblyOrder::hasActiveForInvoice((int) $this->invoiceId)) {
            Flux::toast(__('app.assembly_order_already_exists'), variant: 'warning');

            return;
        }

        $invoice = Invoice::query()->with('customer')->findOrFail($this->invoiceId);

        try {
            $order = AssemblyOrder::createFromInvoice(
                $invoice,
                $this->quantity,
                $this->parts,
                $this->notes ?: null,
                Auth::id(),
            );

            Flux::modal('panels.sale.assembly.convert.modal')->close();
            Flux::toast(__('app.assembly_order_created', ['code' => $order->code]));

            $this->dispatch('panels.sale.assembly.index.render');
            $this->resetForm();
        } catch (Throwable $e) {
            Log::error('Assembly order create failed', ['message' => $e->getMessage()]);
            Flux::toast(__('app.assembly_order_create_failed'), variant: 'danger');
        }
    }

    protected function resetForm(): void
    {
        $this->invoiceId = null;
        $this->invoiceNumber = '';
        $this->customerName = '';
        $this->quantity = 1;
        $this->notes = '';
        $this->aiPrompt = '';
        $this->parts = [];
        $this->isGeneratingAi = false;
    }

    public function render()
    {
        return view('livewire.panels.sale.assembly.convert');
    }
}
