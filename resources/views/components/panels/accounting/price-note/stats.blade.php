<?php

use App\Models\Sepidar\GNR\Grouping;
use App\Models\Sepidar\SLS\InvoiceItem;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $groupingId;

    #[Computed]
    public function salesStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "sales_stats_grouping_v2_{$this->groupingId}_year_" . config('sepidar.FiscalYearRef'),
            now()->addHours(1),
            function () {
                $grouping = Grouping::find($this->groupingId);
                if (!$grouping) {
                    return ['total_quantity' => 0, 'total_amount' => 0, 'total_inventory' => 0];
                }

                $groupIds = $grouping->getAllChildrenIds();
                $fiscalYearRef = config('sepidar.FiscalYearRef');

                $stats = InvoiceItem::query()
                    ->whereHas('invoice', function ($query) use ($fiscalYearRef) {
                        $query->where('FiscalYearRef', $fiscalYearRef);
                    })
                    ->whereHas('item', function ($query) use ($groupIds) {
                        $query->whereIn('CodingGroupRef', $groupIds);
                    })
                    ->selectRaw('SUM(Quantity) as total_quantity, SUM(Quantity * Fee) as total_amount')
                    ->first();

                $inventoryQuantity = \App\Models\Sepidar\INV\ItemStockSummary::query()
                    ->where('FiscalYearRef', $fiscalYearRef)
                    ->whereHas('item', function ($query) use ($groupIds) {
                        $query->whereIn('CodingGroupRef', $groupIds);
                    })
                    ->sum('Quantity');

                return [
                    'total_quantity' => $stats->total_quantity ?? 0,
                    'total_amount' => $stats->total_amount ?? 0,
                    'total_inventory' => $inventoryQuantity ?? 0,
                ];
            }
        );
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 animate-pulse">
            <div class="h-24 bg-gray-200 dark:bg-zinc-800 rounded-xl"></div>
            <div class="h-24 bg-gray-200 dark:bg-zinc-800 rounded-xl"></div>
            <div class="h-24 bg-gray-200 dark:bg-zinc-800 rounded-xl"></div>
        </div>
        HTML;
    }
};
?>

<div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <flux:card class="flex items-center gap-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors" wire:click="$dispatch('panels.accounting.price-note.invoices.assign-data', { groupingId: {{ $groupingId }} })">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <flux:icon icon="shopping-cart" class="text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:subheading>{{ __('app.total_sales_quantity') }}</flux:subheading>
                <flux:heading size="xl">{{ number_format($this->salesStats['total_quantity']) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors" wire:click="$dispatch('panels.accounting.price-note.invoices.assign-data', { groupingId: {{ $groupingId }} })">
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <flux:icon icon="banknote" class="text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:subheading>{{ __('app.total_sales_amount') }}</flux:subheading>
                <flux:heading size="xl">{{ number_format($this->salesStats['total_amount']) }} {{ __('app.rial') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors" wire:click="$dispatch('panels.accounting.price-note.receipts.assign-data', { groupingId: {{ $groupingId }} })">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                <flux:icon icon="package" class="text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:subheading>{{ __('app.total_inventory_quantity') }}</flux:subheading>
                <flux:heading size="xl">{{ number_format($this->salesStats['total_inventory']) }}</flux:heading>
            </div>
        </flux:card>
    </div>
</div>
