<?php

namespace App\Rules;

use App\Models\Sepidar\INV\Item;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ItemPriceNoteFeeRule implements ValidationRule
{
    protected $itemId;

    public function __construct($itemId)
    {
        $this->itemId = $itemId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (int) str_replace(',', '', $value);

        $item = Item::find($this->itemId);

        if (! $item) {
            return;
        }

        $lastPurchasePrice = (int) $item->getLastPurchasePrice();
        $lastSalePrice = (int) $item->getLastSalePrice();

        if ($value < $lastPurchasePrice) {
            $fail(__('app.price_less_than_last_purchase', ['price' => number_format($lastPurchasePrice)]));
        }

        if ($lastSalePrice > 0) {
            $minPrice = $lastSalePrice * 0.7;
            if ($value < $minPrice) {
                $fail(__('app.price_less_than_last_sale_limit', ['price' => number_format($lastSalePrice)]));
            }
        }
    }
}
