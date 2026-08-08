<?php

namespace App\Livewire\Forms\Accounting;

use Livewire\Form;

class InvoiceForm extends Form
{
    public ?int $customer_party_ref = null;

    public int $sale_type_ref = 2;

    public string $date = '';

    public string $description = '';

    /** @var list<array{item_ref: int|null, quantity: string|int|float, fee: string|int|float, discount: string|int|float, description: string}> */
    public array $items = [];

    public function rules(): array
    {
        return [
            'customer_party_ref' => ['required', 'integer'],
            'sale_type_ref' => ['required', 'integer', 'in:1,2'],
            'date' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_ref' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.fee' => ['required', 'numeric', 'gte:0'],
            'items.*.discount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'customer_party_ref' => __('app.customer'),
            'sale_type_ref' => __('app.sale_type'),
            'date' => __('app.date'),
            'description' => __('app.description'),
            'items' => __('app.items'),
            'items.*.item_ref' => __('app.item'),
            'items.*.quantity' => __('app.quantity'),
            'items.*.fee' => __('app.fee'),
            'items.*.discount' => __('app.discount'),
            'items.*.description' => __('app.description'),
        ];
    }
}
