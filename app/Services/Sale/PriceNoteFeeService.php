<?php

namespace App\Services\Sale;

use App\Models\Sepidar\INV\Item;
use App\Models\Sepidar\SLS\PriceNoteItem;
use App\Rules\ItemPriceNoteFeeRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PriceNoteFeeService
{
    public function normalizeFee(mixed $fee): int
    {
        return (int) str_replace(',', '', (string) ($fee ?? 0));
    }

    /**
     * @throws ValidationException
     */
    public function save(int $itemId, mixed $fee): PriceNoteItem
    {
        $normalizedFee = $this->normalizeFee($fee);

        Validator::make(
            ['fee' => $normalizedFee],
            ['fee' => ['required', 'integer', 'min:1', new ItemPriceNoteFeeRule($itemId)]],
            [],
            ['fee' => __('app.fee')]
        )->validate();

        $priceNoteItem = PriceNoteItem::query()
            ->where('ItemRef', $itemId)
            ->first();

        if ($priceNoteItem) {
            $priceNoteItem->update([
                'Fee' => $normalizedFee,
                'Discount' => 0,
            ]);

            return $priceNoteItem->fresh();
        }

        $lastId = (int) (PriceNoteItem::query()->max('PriceNoteItemID') ?? 0);

        return PriceNoteItem::query()->create([
            'PriceNoteItemID' => $lastId + 1,
            'PriceNoteRef' => 1,
            'SaleTypeRef' => 1,
            'ItemRef' => $itemId,
            'UnitRef' => 1,
            'Fee' => $normalizedFee,
            'CurrencyRef' => 1,
            'Discount' => 0,
            'CanChangeInvoiceFee' => 1,
            'CanChangeInvoiceDiscount' => 1,
            'AdditionRate' => 0,
            'EnforceFeeMargins' => 0,
        ]);
    }

    public function itemName(int $itemId): string
    {
        return Item::query()->where('ItemID', $itemId)->value('Title')
            ?? __('app.not_specified');
    }
}
