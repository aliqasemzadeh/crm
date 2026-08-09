<?php

namespace App\Livewire\Forms\Warehouse;

use Livewire\Form;
use Morilog\Jalali\Jalalian;

class InventoryDeliveryForm extends Form
{
    public ?int $stock_ref = null;

    public ?int $receiver_party_ref = null;

    public string $date = '';

    public string $description = '';

    /**
     * @var list<array{item_ref: int|null, quantity: float|int|string, description: string}>
     */
    public array $items = [];

    public function ensureDefaults(): void
    {
        if ($this->stock_ref === null) {
            $this->stock_ref = (int) config('sepidar.DefaultStockRef', 2);
        }

        if ($this->date === '') {
            $this->date = Jalalian::now()->format('Y/m/d');
        }

        if ($this->items === []) {
            $this->items = [
                $this->emptyRow(),
            ];
        }
    }

    /**
     * @return array{item_ref: null, quantity: int, description: string}
     */
    public function emptyRow(): array
    {
        return [
            'item_ref' => null,
            'quantity' => 1,
            'description' => '',
        ];
    }

    public function rules(): array
    {
        return [
            'stock_ref' => ['required', 'integer', 'min:1'],
            'receiver_party_ref' => ['nullable', 'integer', 'min:1'],
            'date' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_ref' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'stock_ref' => __('app.warehouse_stock'),
            'receiver_party_ref' => __('app.warehouse_delivery_receiver'),
            'date' => __('app.date'),
            'description' => __('app.description'),
            'items' => __('app.items'),
            'items.*.item_ref' => __('app.item'),
            'items.*.quantity' => __('app.quantity'),
            'items.*.description' => __('app.description'),
        ];
    }

    /**
     * @return array{
     *     stock_ref: int,
     *     receiver_party_ref: int|null,
     *     date: string,
     *     description: string|null,
     *     items: list<array{item_ref: int, quantity: float, description: string|null}>
     * }
     */
    public function payload(): array
    {
        return [
            'stock_ref' => (int) $this->stock_ref,
            'receiver_party_ref' => $this->receiver_party_ref ? (int) $this->receiver_party_ref : null,
            'date' => $this->date,
            'description' => $this->description !== '' ? $this->description : null,
            'items' => collect($this->items)
                ->filter(fn (array $row) => (int) ($row['item_ref'] ?? 0) > 0)
                ->map(fn (array $row) => [
                    'item_ref' => (int) $row['item_ref'],
                    'quantity' => (float) str_replace(',', '', (string) $row['quantity']),
                    'description' => ($row['description'] ?? '') !== '' ? (string) $row['description'] : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
