<?php

namespace App\Livewire\Forms\Crm;

use Livewire\Form;

class ReturnDiscountGenerateForm extends Form
{
    public mixed $discount_amount = null;

    public ?int $usage_duration_days = null;

    public string $sms_text = '';

    public string $site_url = 'https://setaregan.co';

    public bool $for_special_offer = false;

    public function normalizeAmounts(): void
    {
        $this->discount_amount = $this->toInteger($this->discount_amount);
    }

    public function rules(): array
    {
        return [
            'discount_amount' => ['required', 'integer', 'min:1'],
            'usage_duration_days' => ['required', 'integer', 'min:1'],
            'sms_text' => ['required', 'string', 'min:3'],
            'site_url' => ['nullable', 'string', 'max:255'],
            'for_special_offer' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'discount_amount' => __('app.cash_back_amount'),
            'usage_duration_days' => __('app.cash_back_usage_duration_days'),
            'sms_text' => __('app.cash_back_generator_sms_text'),
            'site_url' => __('app.cash_back_generator_site_url'),
            'for_special_offer' => __('app.cash_back_generator_for_special_offer'),
        ];
    }

    private function toInteger(mixed $value): int
    {
        return (int) str_replace(',', '', (string) ($value ?? 0));
    }
}
