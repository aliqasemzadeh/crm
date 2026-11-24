<?php

namespace App\Livewire\Forms\Warehouse;

use App\Enums\WarehouseWarrantyShipmentStatusEnum;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Morilog\Jalali\Jalalian;

class WarrantyShipmentForm extends Form
{
    public string $item_title = '';

    public int|string $quantity = 1;

    public string $serial_number = '';

    public string $warranty_company = '';

    public string $sent_at = '';

    public string $received_at = '';

    public string $status = '';

    public string $description = '';

    public function ensureDefaults(): void
    {
        if ($this->sent_at === '') {
            $this->sent_at = Jalalian::now()->format('Y/m/d');
        }

        if ($this->status === '') {
            $this->status = WarehouseWarrantyShipmentStatusEnum::Sent->value;
        }

        if ($this->quantity === '' || (int) $this->quantity < 1) {
            $this->quantity = 1;
        }
    }

    public function rules(): array
    {
        return [
            'item_title' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'warranty_company' => ['required', 'string', 'max:255'],
            'sent_at' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{2}\/\d{2}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        Jalalian::fromFormat('Y/m/d', (string) $value);
                    } catch (\Throwable) {
                        $fail(__('validation.date', ['attribute' => __('app.warehouse_warranty_sent_at')]));
                    }
                },
            ],
            'received_at' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    if (! preg_match('/^\d{4}\/\d{2}\/\d{2}$/', (string) $value)) {
                        $fail(__('validation.date', ['attribute' => __('app.warehouse_warranty_received_at')]));

                        return;
                    }

                    try {
                        Jalalian::fromFormat('Y/m/d', (string) $value);
                    } catch (\Throwable) {
                        $fail(__('validation.date', ['attribute' => __('app.warehouse_warranty_received_at')]));
                    }
                },
            ],
            'status' => ['required', 'string', Rule::enum(WarehouseWarrantyShipmentStatusEnum::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'item_title' => __('app.warehouse_warranty_item_title'),
            'quantity' => __('app.warehouse_warranty_quantity'),
            'serial_number' => __('app.warehouse_warranty_serial_number'),
            'warranty_company' => __('app.warehouse_warranty_company'),
            'sent_at' => __('app.warehouse_warranty_sent_at'),
            'received_at' => __('app.warehouse_warranty_received_at'),
            'status' => __('app.status'),
            'description' => __('app.description'),
        ];
    }

    /**
     * @return array{
     *     item_title: string,
     *     quantity: int,
     *     serial_number: string|null,
     *     warranty_company: string,
     *     sent_at: string,
     *     received_at: string|null,
     *     status: string,
     *     description: string|null
     * }
     */
    public function payload(): array
    {
        $this->validate();

        return [
            'item_title' => trim($this->item_title),
            'quantity' => (int) $this->quantity,
            'serial_number' => $this->serial_number !== '' ? trim($this->serial_number) : null,
            'warranty_company' => trim($this->warranty_company),
            'sent_at' => Jalalian::fromFormat('Y/m/d', $this->sent_at)->toCarbon()->toDateString(),
            'received_at' => $this->received_at !== ''
                ? Jalalian::fromFormat('Y/m/d', $this->received_at)->toCarbon()->toDateString()
                : null,
            'status' => $this->status,
            'description' => $this->description !== '' ? trim($this->description) : null,
        ];
    }
}
