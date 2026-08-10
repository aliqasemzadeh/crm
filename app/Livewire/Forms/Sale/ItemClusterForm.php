<?php

namespace App\Livewire\Forms\Sale;

use Livewire\Form;

class ItemClusterForm extends Form
{
    public string $title = '';

    public string $description = '';

    /** @var array<int, int> */
    public array $item_refs = [];

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'item_refs' => ['required', 'array', 'min:1'],
            'item_refs.*' => ['integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => __('app.title'),
            'description' => __('app.description'),
            'item_refs' => __('app.items'),
            'item_refs.*' => __('app.items'),
            'is_active' => __('app.active'),
        ];
    }

    /**
     * @return array{title: string, description: ?string, item_refs: array<int, int>, is_active: bool}
     */
    public function toPayload(): array
    {
        return [
            'title' => trim($this->title),
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
            'item_refs' => collect($this->item_refs)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
