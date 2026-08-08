<?php

namespace App\Livewire\Forms\Crm;

use Livewire\Form;

class CashBackRuleForm extends Form
{
    public mixed $start_amount = null;

    public mixed $end_amount = null;

    public mixed $cash_back_amount = null;

    public bool $is_percent = false;

    public ?int $activation_delay_days = null;

    public ?int $usage_duration_days = null;

    public function normalizeAmounts(): void
    {
        $this->start_amount = $this->toInteger($this->start_amount);
        $this->end_amount = $this->toInteger($this->end_amount);
        $this->cash_back_amount = $this->toInteger($this->cash_back_amount);
    }

    public function rules(): array
    {
        return [
            'start_amount' => ['required', 'integer', 'min:0'],
            'end_amount' => ['required', 'integer', 'gte:start_amount'],
            'cash_back_amount' => ['required', 'integer', 'min:1'],
            'is_percent' => ['boolean'],
            'activation_delay_days' => ['required', 'integer', 'min:0'],
            'usage_duration_days' => ['required', 'integer', 'min:1'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'start_amount' => __('app.cash_back_start_amount'),
            'end_amount' => __('app.cash_back_end_amount'),
            'cash_back_amount' => __('app.cash_back_amount'),
            'is_percent' => __('app.cash_back_is_percent'),
            'activation_delay_days' => __('app.cash_back_activation_delay_days'),
            'usage_duration_days' => __('app.cash_back_usage_duration_days'),
        ];
    }

    private function toInteger(mixed $value): int
    {
        return (int) str_replace(',', '', (string) ($value ?? 0));
    }
}
