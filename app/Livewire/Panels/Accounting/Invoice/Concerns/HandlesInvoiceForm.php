<?php

namespace App\Livewire\Panels\Accounting\Invoice\Concerns;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\INV\ItemStockSummary;
use App\Services\Sepidar\PartyBalance;
use App\Services\Sepidar\PartyCreator;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

trait HandlesInvoiceForm
{
    public array $items = [];

    public string $partySearch = '';

    public string $itemSearch = '';

    public int $itemSearchRow = -1;

    public string $newPartyName = '';

    public string $newPartyLastName = '';

    public string $newPartyMobile = '';

    /** @var array<string, float>|null */
    public ?array $partyBalance = null;

    public function addRow(): void
    {
        $this->items[] = $this->emptyRow();
    }

    public function insertRowAfter(int $index): void
    {
        array_splice($this->items, $index + 1, 0, [$this->emptyRow()]);
        $this->items = array_values($this->items);
    }

    public function removeRow(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function sortItems(string $id, int $position): void
    {
        $currentIndex = collect($this->items)->search(
            fn (array $row): bool => (string) ($row['row_id'] ?? '') === $id
        );

        if ($currentIndex === false) {
            return;
        }

        $items = $this->items;
        $item = $items[$currentIndex];
        unset($items[$currentIndex]);
        $items = array_values($items);
        array_splice($items, $position, 0, [$item]);
        $this->items = array_values($items);
    }

    public function updatedFormCustomerPartyRef(): void
    {
        $this->partySearch = '';
        unset($this->selectedParty, $this->partyResults);

        if ($this->form->customer_party_ref) {
            $this->showPartyBalance((int) $this->form->customer_party_ref);
        } else {
            $this->partyBalance = null;
        }
    }

    public function updatedFormSaleTypeRef(): void
    {
        $this->recalculateLineTaxes();
    }

    public function updatedItems(mixed $value, string $key): void
    {
        if (str_ends_with($key, '.quantity')
            || str_ends_with($key, '.fee')
            || str_ends_with($key, '.discount')) {
            $index = (int) Str::before($key, '.');
            $this->recalculateLineTax($index);
        }
    }

    protected function invoiceUiPrefix(): string
    {
        return 'panels.accounting.invoice';
    }

    protected function invoiceModal(string $suffix): string
    {
        return $this->invoiceUiPrefix().'.'.$suffix;
    }

    public function openItemSearch(int $index): void
    {
        $this->itemSearchRow = $index;
        $this->itemSearch = '';
        unset($this->itemResults);
        Flux::modal($this->invoiceModal('item-search.modal'))->show();
    }

    public function selectItem(int $itemId): void
    {
        $index = $this->itemSearchRow;

        if ($index < 0 || ! isset($this->items[$index])) {
            return;
        }

        $fromResults = collect($this->itemResults)->firstWhere('id', $itemId);
        $fee = (int) ($fromResults['last_sale_price'] ?? 0);

        if ($fee <= 0) {
            $fee = (int) (Item::query()->find($itemId)?->getLastSalePrice() ?? 0);
        }

        $this->items[$index]['item_ref'] = $itemId;
        $this->items[$index]['fee'] = $fee;
        $this->recalculateLineTax($index);

        $this->itemSearch = '';
        $this->itemSearchRow = -1;
        unset($this->itemResults, $this->selectedItems);

        Flux::modal($this->invoiceModal('item-search.modal'))->close();
    }

    public function clearParty(): void
    {
        $this->form->customer_party_ref = null;
        $this->partySearch = '';
        $this->partyBalance = null;
        unset($this->selectedParty, $this->partyResults);
    }

    public function clearItem(int $index): void
    {
        $this->items[$index]['item_ref'] = null;
        $this->items[$index]['fee'] = 0;
        $this->items[$index]['tax'] = 0;
        unset($this->selectedItems);
        $this->openItemSearch($index);
    }

    public function showPartyBalance(int $partyId): void
    {
        $this->partyBalance = app(PartyBalance::class)->forParty($partyId);
        Flux::modal($this->invoiceModal('party-balance.modal'))->show();
    }

    public function openPreview(): void
    {
        Flux::modal($this->invoiceModal('preview.modal'))->show();
    }

    public function createParty(PartyCreator $creator): void
    {
        if (
            ! auth()->user()?->can('accounting_party_create')
            && ! auth()->user()?->can('accounting_invoice_create')
            && ! auth()->user()?->can('sales_party_create')
            && ! auth()->user()?->can('sales_invoice_create')
        ) {
            abort(403);
        }

        $validated = $this->validate([
            'newPartyName' => ['required', 'string', 'max:255'],
            'newPartyLastName' => ['nullable', 'string', 'max:255'],
            'newPartyMobile' => ['required', 'string', 'max:20'],
        ], [], [
            'newPartyName' => __('app.first_name'),
            'newPartyLastName' => __('app.last_name'),
            'newPartyMobile' => __('app.mobile'),
        ]);

        try {
            $party = $creator->create([
                'name' => $validated['newPartyName'],
                'last_name' => $validated['newPartyLastName'] ?? '',
                'mobile' => $validated['newPartyMobile'],
            ]);
        } catch (\Throwable $e) {
            report($e);
            Flux::toast(__('app.party_create_failed'), variant: 'danger');

            return;
        }

        $this->form->customer_party_ref = (int) $party->PartyId;
        $this->partySearch = '';
        $this->newPartyName = '';
        $this->newPartyLastName = '';
        $this->newPartyMobile = '';
        unset($this->selectedParty, $this->partyResults);

        Flux::modal($this->invoiceModal('party-create.modal'))->close();
        Flux::toast(__('app.party_created'));
        $this->showPartyBalance((int) $party->PartyId);
    }

    #[Computed]
    public function partyResults()
    {
        $term = trim($this->partySearch);

        if (Str::length($term) < 3) {
            return collect();
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
            ->where(function ($query) use ($term) {
                $query->where('Name', 'like', '%'.$term.'%')
                    ->orWhere('LastName', 'like', '%'.$term.'%')
                    ->orWhere('Name_En', 'like', '%'.$term.'%')
                    ->orWhere('LastName_En', 'like', '%'.$term.'%')
                    ->orWhereRaw("(ISNULL([Name], '') + ' ' + ISNULL([LastName], '')) LIKE ?", ['%'.$term.'%']);
            })
            ->orderBy('PartyId')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function selectedParty(): ?Party
    {
        if (! $this->form->customer_party_ref) {
            return null;
        }

        return Party::query()
            ->select(['PartyId', 'Name', 'LastName', 'Name_En', 'LastName_En'])
            ->find($this->form->customer_party_ref);
    }

    #[Computed]
    public function itemResults()
    {
        $term = trim($this->itemSearch);

        if (Str::length($term) < 3) {
            return collect();
        }

        $fiscalYearRef = (string) config('sepidar.FiscalYearRef');

        $items = Item::query()
            ->with(['image', 'product'])
            ->select(['ItemID', 'Title', 'Code', 'IranCode'])
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%')
                    ->orWhere('IranCode', 'like', '%'.$term.'%');
            })
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM [INV].[ItemImage] WHERE [INV].[ItemImage].[ItemRef] = [INV].[Item].[ItemID]) THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN [IranCode] IS NOT NULL AND LTRIM(RTRIM([IranCode])) <> '' THEN 0 ELSE 1 END")
            ->orderBy('Title')
            ->limit(15)
            ->get();

        $ids = $items->pluck('ItemID')->all();

        if ($ids === []) {
            return collect();
        }

        $stocks = ItemStockSummary::query()
            ->whereIn('ItemRef', $ids)
            ->where('FiscalYearRef', $fiscalYearRef)
            ->selectRaw('ItemRef, SUM(CAST(Quantity AS DECIMAL(18,4))) as total_quantity')
            ->groupBy('ItemRef')
            ->pluck('total_quantity', 'ItemRef');

        $lastSales = $this->batchLatestFees('[SLS].[InvoiceItem]', 'InvoiceItemID', 'ItemRef', 'Fee', $ids);
        $lastPurchases = $this->batchLatestFees('[INV].[InventoryReceiptItem]', 'InventoryReceiptItemID', 'ItemRef', 'Fee', $ids);

        return $items->map(function (Item $item) use ($stocks, $lastSales, $lastPurchases) {
            $id = (int) $item->ItemID;

            return [
                'id' => $id,
                'title' => $item->Title,
                'code' => $item->Code,
                'thumbnail' => $item->image?->Thumbnail,
                'stock' => (float) ($stocks[$item->ItemID] ?? 0),
                'last_sale_price' => (float) ($lastSales[$id] ?? 0),
                'last_purchase_price' => (float) ($lastPurchases[$id] ?? 0),
                'site_price' => $item->siteMinPriceRial(),
            ];
        });
    }

    #[Computed]
    public function selectedItems(): array
    {
        $ids = collect($this->items)
            ->pluck('item_ref')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return Item::query()
            ->with('image')
            ->select(['ItemID', 'Title', 'Code'])
            ->whereIn('ItemID', $ids)
            ->get()
            ->keyBy(fn (Item $item) => (int) $item->ItemID)
            ->all();
    }

    #[Computed]
    public function totals(): array
    {
        $price = 0;
        $discount = 0;
        $tax = 0;

        foreach ($this->items as $row) {
            $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
            $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
            $rowDiscount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
            $rowTax = (float) str_replace(',', '', (string) ($row['tax'] ?? 0));
            $price += $qty * $fee;
            $discount += $rowDiscount;
            $tax += $rowTax;
        }

        return [
            'price' => $price,
            'discount' => $discount,
            'tax' => $tax,
            'net' => $price - $discount + $tax,
        ];
    }

    #[Computed]
    public function issuerName(): string
    {
        $user = auth()->user();

        if (! $user) {
            return '-';
        }

        $user->loadMissing('sepidarUser');
        $name = $user->sepidarUser?->Name;

        if (filled($name)) {
            return (string) $name;
        }

        return $user->name !== '' ? $user->name : '-';
    }

    public function partyName(?Party $party): string
    {
        if (! $party) {
            return '-';
        }

        return trim(implode(' ', array_filter([
            trim((string) ($party->Name ?? '')),
            trim((string) ($party->LastName ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }

    public function taxRate(): float
    {
        return (int) $this->form->sale_type_ref === 1 ? 0.09 : 0.0;
    }

    protected function emptyRow(): array
    {
        return [
            'row_id' => (string) Str::uuid(),
            'item_ref' => null,
            'quantity' => 1,
            'fee' => 0,
            'discount' => 0,
            'tax' => 0,
            'description' => '',
        ];
    }

    protected function normalizeMoneyFields(): void
    {
        foreach ($this->items as $index => $row) {
            foreach (['quantity', 'fee', 'discount', 'tax'] as $field) {
                $this->items[$index][$field] = (float) str_replace(',', '', (string) ($row[$field] ?? 0));
            }
        }
    }

    protected function recalculateLineTax(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $qty = (float) str_replace(',', '', (string) ($this->items[$index]['quantity'] ?? 0));
        $fee = (float) str_replace(',', '', (string) ($this->items[$index]['fee'] ?? 0));
        $discount = (float) str_replace(',', '', (string) ($this->items[$index]['discount'] ?? 0));
        $base = max(($qty * $fee) - $discount, 0);
        $this->items[$index]['tax'] = (int) round($base * $this->taxRate());
    }

    protected function recalculateLineTaxes(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->recalculateLineTax((int) $index);
        }
    }

    protected function validateInvoice(): void
    {
        $this->normalizeMoneyFields();
        $this->recalculateLineTaxes();

        $this->form->validate();

        $this->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_ref' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.fee' => ['required', 'numeric', 'gte:0'],
            'items.*.discount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax' => ['nullable', 'numeric', 'gte:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ], [], [
            'items' => __('app.items'),
            'items.*.item_ref' => __('app.item'),
            'items.*.quantity' => __('app.quantity'),
            'items.*.fee' => __('app.fee'),
            'items.*.discount' => __('app.discount'),
            'items.*.tax' => __('app.tax'),
            'items.*.description' => __('app.description'),
        ]);
    }

    protected function formPayload(): array
    {
        $this->normalizeMoneyFields();
        $this->recalculateLineTaxes();

        return [
            'customer_party_ref' => (int) $this->form->customer_party_ref,
            'sale_type_ref' => (int) $this->form->sale_type_ref,
            'date' => $this->form->date,
            'description' => $this->form->description !== '' ? $this->form->description : null,
            'items' => collect($this->items)->map(function (array $row) {
                return [
                    'item_ref' => (int) $row['item_ref'],
                    'quantity' => (float) $row['quantity'],
                    'fee' => (float) $row['fee'],
                    'discount' => (float) ($row['discount'] ?? 0),
                    'tax' => (float) ($row['tax'] ?? 0),
                    'description' => ($row['description'] ?? '') !== '' ? $row['description'] : null,
                ];
            })->all(),
        ];
    }

    /**
     * @param  list<int|string>  $itemIds
     * @return array<int, float>
     */
    protected function batchLatestFees(string $table, string $idColumn, string $itemColumn, string $feeColumn, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

        $sql = "
            SELECT t.[{$itemColumn}] as item_ref, t.[{$feeColumn}] as fee
            FROM (
                SELECT [{$itemColumn}], [{$feeColumn}],
                    ROW_NUMBER() OVER (PARTITION BY [{$itemColumn}] ORDER BY [{$idColumn}] DESC) as rn
                FROM {$table}
                WHERE [{$itemColumn}] IN ({$placeholders})
            ) t
            WHERE t.rn = 1
        ";

        $rows = DB::connection('sqlsrv')->select($sql, $itemIds);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->item_ref] = (float) $row->fee;
        }

        return $result;
    }
}
