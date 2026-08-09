<?php

namespace App\Livewire\Forms\Crm;

use Livewire\Form;

class CustomerSearchForm extends Form
{
    public string $search = '';

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $no_purchase_since = null;

    public mixed $min_count = null;

    public mixed $max_count = null;

    public mixed $min_amount = null;

    public mixed $max_amount = null;

    public function normalize(): void
    {
        $this->min_count = $this->toNullableInteger($this->min_count);
        $this->max_count = $this->toNullableInteger($this->max_count);
        $this->min_amount = $this->toNullableInteger($this->min_amount);
        $this->max_amount = $this->toNullableInteger($this->max_amount);
        $this->search = trim($this->search);
        $this->date_from = $this->blankToNull($this->date_from);
        $this->date_to = $this->blankToNull($this->date_to);
        $this->no_purchase_since = $this->blankToNull($this->no_purchase_since);
    }

    public function hasActiveFilters(): bool
    {
        $this->normalize();

        return $this->date_from !== null
            || $this->date_to !== null
            || $this->no_purchase_since !== null
            || $this->min_count !== null
            || $this->max_count !== null
            || $this->min_amount !== null
            || $this->max_amount !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'string'],
            'date_to' => ['nullable', 'string'],
            'no_purchase_since' => ['nullable', 'string'],
            'min_count' => ['nullable', 'integer', 'min:0'],
            'max_count' => ['nullable', 'integer', 'min:0', 'gte:min_count'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_amount' => ['nullable', 'integer', 'min:0', 'gte:min_amount'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'search' => __('app.search'),
            'date_from' => __('app.from_date'),
            'date_to' => __('app.to_date'),
            'no_purchase_since' => __('app.customer_search_no_purchase_since'),
            'min_count' => __('app.customer_search_min_count'),
            'max_count' => __('app.customer_search_max_count'),
            'min_amount' => __('app.customer_search_min_amount'),
            'max_amount' => __('app.customer_search_max_amount'),
        ];
    }

    public function clear(): void
    {
        $this->search = '';
        $this->date_from = null;
        $this->date_to = null;
        $this->no_purchase_since = null;
        $this->min_count = null;
        $this->max_count = null;
        $this->min_amount = null;
        $this->max_amount = null;
    }

    private function toNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '', (string) $value);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
