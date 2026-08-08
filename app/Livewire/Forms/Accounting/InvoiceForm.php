<?php

namespace App\Livewire\Forms\Accounting;

use Livewire\Form;

class InvoiceForm extends Form
{
    public ?int $customer_party_ref = null;

    public int $sale_type_ref = 2;

    public string $date = '';

    public string $description = '';

    public ?int $delivery_location_ref = null;

    public function rules(): array
    {
        return [
            'customer_party_ref' => ['required', 'integer'],
            'sale_type_ref' => ['required', 'integer', 'in:1,2'],
            'date' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'delivery_location_ref' => ['required', 'integer'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'customer_party_ref' => __('app.customer'),
            'sale_type_ref' => __('app.sale_type'),
            'date' => __('app.date'),
            'description' => __('app.description'),
            'delivery_location_ref' => __('app.delivery_location'),
        ];
    }
}
