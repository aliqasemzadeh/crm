<?php

namespace App\Livewire\Forms\Crm;

use Livewire\Form;

class CashBackGeneratorForm extends Form
{
    public ?string $from_date = null;

    public ?int $min_order_count = null;

    public mixed $min_total_amount = null;

    public mixed $discount_amount = null;

    public ?int $usage_duration_days = null;

    public string $sms_text = '';

    public function normalizeAmounts(): void
    {
        $this->min_total_amount = $this->toInteger($this->min_total_amount);
        $this->discount_amount = $this->toInteger($this->discount_amount);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'string'],
            'min_order_count' => ['required', 'integer', 'min:1'],
            'min_total_amount' => ['required', 'integer', 'min:0'],
            'discount_amount' => ['required', 'integer', 'min:1'],
            'usage_duration_days' => ['required', 'integer', 'min:1'],
            'sms_text' => ['required', 'string', 'min:3'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'from_date' => __('app.cash_back_generator_from_date'),
            'min_order_count' => __('app.cash_back_generator_min_order_count'),
            'min_total_amount' => __('app.cash_back_generator_min_total_amount'),
            'discount_amount' => __('app.cash_back_amount'),
            'usage_duration_days' => __('app.cash_back_usage_duration_days'),
            'sms_text' => __('app.cash_back_generator_sms_text'),
        ];
    }

    private function toInteger(mixed $value): int
    {
        return (int) str_replace(',', '', (string) ($value ?? 0));
    }
}
