<?php

namespace App\Livewire\Panels\Accounting\Invoice\Concerns;

use App\Models\Sepidar\GNR\Party;
use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use App\Services\Sepidar\PartyCreator;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

trait HandlesInvoiceForm
{
    public string $partySearch = '';

    public string $itemSearch = '';

    public int $itemSearchRow = -1;

    public string $newPartyName = '';

    public string $newPartyLastName = '';

    public string $newPartyMobile = '';

    public function addRow(): void
    {
        $this->form->items[] = $this->emptyRow();
    }

    public function insertRowAfter(int $index): void
    {
        $items = $this->form->items;
        array_splice($items, $index + 1, 0, [$this->emptyRow()]);
        $this->form->items = array_values($items);
    }

    public function removeRow(int $index): void
    {
        if (count($this->form->items) <= 1) {
            return;
        }

        unset($this->form->items[$index]);
        $this->form->items = array_values($this->form->items);
    }

    public function updatedFormCustomerPartyRef(): void
    {
        $this->partySearch = '';
        unset($this->selectedParty, $this->partyResults);
    }

    public function updatedFormSaleTypeRef(): void
    {
        foreach ($this->form->items as $index => $row) {
            $itemRef = (int) ($row['item_ref'] ?? 0);
            if ($itemRef > 0) {
                $this->applyItemFee($index, $itemRef);
            }
        }
    }

    public function updatedFormItems(mixed $value, string $key): void
    {
        if (! str_ends_with($key, '.item_ref')) {
            return;
        }

        $index = (int) Str::before($key, '.');
        $itemRef = (int) ($this->form->items[$index]['item_ref'] ?? 0);

        if ($itemRef <= 0) {
            return;
        }

        $this->applyItemFee($index, $itemRef);
        $this->itemSearch = '';
        $this->itemSearchRow = -1;
        unset($this->itemResults, $this->selectedItems);
    }

    public function openItemSearch(int $index): void
    {
        $this->itemSearchRow = $index;
        $this->itemSearch = '';
        unset($this->itemResults);
    }

    public function clearParty(): void
    {
        $this->form->customer_party_ref = null;
        $this->partySearch = '';
        unset($this->selectedParty, $this->partyResults);
    }

    public function clearItem(int $index): void
    {
        $this->form->items[$index]['item_ref'] = null;
        $this->form->items[$index]['fee'] = 0;
        $this->itemSearchRow = $index;
        $this->itemSearch = '';
        unset($this->itemResults, $this->selectedItems);
    }

    public function openPreview(): void
    {
        Flux::modal('panels.accounting.invoice.preview.modal')->show();
    }

    public function createParty(PartyCreator $creator): void
    {
        if (! auth()->user()?->can('accounting_party_create') && ! auth()->user()?->can('accounting_invoice_create')) {
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

        Flux::modal('panels.accounting.invoice.party-create.modal')->close();
        Flux::toast(__('app.party_created'));
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

        return Item::query()
            ->with('image')
            ->select(['ItemID', 'Title', 'Code', 'IranCode'])
            ->where(function ($query) use ($term) {
                $query->where('Title', 'like', '%'.$term.'%')
                    ->orWhere('Code', 'like', '%'.$term.'%')
                    ->orWhere('IranCode', 'like', '%'.$term.'%');
            })
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM [INV].[ItemImage] WHERE [INV].[ItemImage].[ItemRef] = [INV].[Item].[ItemID]) THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN [IranCode] IS NOT NULL AND LTRIM(RTRIM([IranCode])) <> '' THEN 0 ELSE 1 END")
            ->orderBy('Title')
            ->limit(40)
            ->get();
    }

    #[Computed]
    public function selectedItems(): array
    {
        $ids = collect($this->form->items)
            ->pluck('item_ref')
            ->filter()
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
            ->keyBy('ItemID')
            ->all();
    }

    #[Computed]
    public function totals(): array
    {
        $price = 0;
        $discount = 0;

        foreach ($this->form->items as $row) {
            $qty = (float) str_replace(',', '', (string) ($row['quantity'] ?? 0));
            $fee = (float) str_replace(',', '', (string) ($row['fee'] ?? 0));
            $rowDiscount = (float) str_replace(',', '', (string) ($row['discount'] ?? 0));
            $price += $qty * $fee;
            $discount += $rowDiscount;
        }

        return [
            'price' => $price,
            'discount' => $discount,
            'net' => $price - $discount,
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

    protected function emptyRow(): array
    {
        return [
            'item_ref' => null,
            'quantity' => 1,
            'fee' => 0,
            'discount' => 0,
            'description' => '',
        ];
    }

    protected function normalizeMoneyFields(): void
    {
        foreach ($this->form->items as $index => $row) {
            foreach (['quantity', 'fee', 'discount'] as $field) {
                $this->form->items[$index][$field] = (float) str_replace(',', '', (string) ($row[$field] ?? 0));
            }
        }
    }

    protected function formPayload(): array
    {
        $this->normalizeMoneyFields();

        return [
            'customer_party_ref' => (int) $this->form->customer_party_ref,
            'sale_type_ref' => (int) $this->form->sale_type_ref,
            'date' => $this->form->date,
            'description' => $this->form->description !== '' ? $this->form->description : null,
            'items' => collect($this->form->items)->map(function (array $row) {
                return [
                    'item_ref' => (int) $row['item_ref'],
                    'quantity' => (float) $row['quantity'],
                    'fee' => (float) $row['fee'],
                    'discount' => (float) ($row['discount'] ?? 0),
                    'description' => ($row['description'] ?? '') !== '' ? $row['description'] : null,
                ];
            })->all(),
        ];
    }

    protected function applyItemFee(int $index, int $itemRef): void
    {
        $fee = PriceNoteItem::query()
            ->where('ItemRef', $itemRef)
            ->where('SaleTypeRef', $this->form->sale_type_ref)
            ->value('Fee');

        if ($fee === null) {
            $item = Item::query()->find($itemRef);
            $fee = $item?->getLastSalePrice() ?? 0;
        }

        if ($fee === null || (float) $fee === 0.0) {
            $fee = PriceNoteItem::query()
                ->where('ItemRef', $itemRef)
                ->value('Fee') ?? 0;
        }

        $this->form->items[$index]['fee'] = (int) $fee;
    }
}
