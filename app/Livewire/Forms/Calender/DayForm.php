<?php

namespace App\Livewire\Forms\Calender;

use App\Models\Calender\Day;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Morilog\Jalali\Jalalian;

class DayForm extends Form
{
    public string $date = '';

    public string $title = '';

    public ?int $dayId = null;

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{2}\/\d{2}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        Jalalian::fromFormat('Y/m/d', (string) $value);
                    } catch (\Throwable) {
                        $fail(__('validation.date', ['attribute' => __('app.date')]));
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'date' => __('app.date'),
            'title' => __('app.title'),
        ];
    }

    public function toGregorianDate(): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->date)
            ->toCarbon()
            ->toDateString();
    }

    /**
     * @return array{date: string, title: string}
     */
    public function validateForSave(): array
    {
        $this->validate();

        $gregorian = $this->toGregorianDate();

        $exists = Day::query()
            ->whereDate('date', $gregorian)
            ->when($this->dayId, fn ($q) => $q->where('id', '!=', $this->dayId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'form.date' => [__('validation.unique', ['attribute' => __('app.date')])],
            ]);
        }

        return [
            'date' => $gregorian,
            'title' => $this->title,
        ];
    }
}
