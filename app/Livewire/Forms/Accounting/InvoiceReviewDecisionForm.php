<?php

namespace App\Livewire\Forms\Accounting;

use App\Enums\InvoiceReviewStatusEnum;
use Illuminate\Validation\Rule;
use Livewire\Form;

class InvoiceReviewDecisionForm extends Form
{
    public string $decision = '';

    public string $note = '';

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in([
                    InvoiceReviewStatusEnum::APPROVED->value,
                    InvoiceReviewStatusEnum::REJECTED->value,
                ]),
            ],
            'note' => [
                Rule::requiredIf(fn () => $this->decision === InvoiceReviewStatusEnum::REJECTED->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'decision' => __('app.invoice_review_decision'),
            'note' => __('app.invoice_review_note'),
        ];
    }
}
