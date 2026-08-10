<?php

use App\Services\Sale\PriceNoteFeeService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $itemId;

    public string $title = '';

    public string $code = '';

    public mixed $fee = '';

    public bool $feeSaved = false;

    public function mount(int $itemId, string $title, string $code, int $initialFee = 0): void
    {
        $this->itemId = $itemId;
        $this->title = $title;
        $this->code = $code;
        $this->fee = $initialFee > 0 ? number_format($initialFee) : '';
    }

    public function updatedFee(): void
    {
        $this->feeSaved = false;
    }

    public function save(PriceNoteFeeService $priceNoteFeeService): void
    {
        $this->authorize('sales_item_fee');

        $priceNoteFeeService->save($this->itemId, $this->fee);
        $this->feeSaved = true;

        Flux::toast(__('app.saved_successfully', [
            'name' => $this->title !== '' ? $this->title : $priceNoteFeeService->itemName($this->itemId),
        ]));
    }

    #[On('panels.sale.quick-pricing.save-all')]
    public function saveFromBatch(PriceNoteFeeService $priceNoteFeeService): void
    {
        if (! auth()->user()?->can('sales_item_fee')) {
            return;
        }

        $normalized = $priceNoteFeeService->normalizeFee($this->fee);
        if ($normalized < 1) {
            return;
        }

        $priceNoteFeeService->save($this->itemId, $normalized);
        $this->feeSaved = true;
    }
};
?>

<flux:table.row wire:key="quick-pricing-row-{{ $itemId }}">
    <flux:table.cell class="whitespace-nowrap">{{ $code }}</flux:table.cell>
    <flux:table.cell>
        <div class="font-medium">{{ $title }}</div>
    </flux:table.cell>
    <flux:table.cell class="min-w-48">
        @can('sales_item_fee')
            <flux:input
                wire:model="fee"
                wire:keydown.enter.prevent="save"
                mask:dynamic="$money($input, '.', ',', 0)"
                :icon-trailing="$feeSaved ? 'check' : ''"
                :class="$feeSaved ? '!border-green-500' : ''"
            />
        @else
            {{ $fee !== '' ? $fee : '—' }}
        @endcan
    </flux:table.cell>
    <flux:table.cell class="whitespace-nowrap">
        @can('sales_item_fee')
            <flux:tooltip content="{{ __('app.save') }}">
                <flux:button
                    size="xs"
                    variant="primary"
                    color="orange"
                    icon="save"
                    icon:variant="outline"
                    wire:click="save"
                />
            </flux:tooltip>
        @endcan
    </flux:table.cell>
</flux:table.row>
