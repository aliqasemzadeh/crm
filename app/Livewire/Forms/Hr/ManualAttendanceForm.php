<?php

namespace App\Livewire\Forms\Hr;

use App\Models\Hr\Record;
use Livewire\Form;
use Morilog\Jalali\Jalalian;

class ManualAttendanceForm extends Form
{
    public string $date = '';

    public string $time = '';

    public string $type = 'clock_in';

    public string $category = Record::CATEGORY_WORK;

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
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'type' => ['required', 'in:clock_in,clock_out'],
            'category' => ['required', 'in:'.implode(',', Record::CATEGORIES)],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'date' => __('app.date'),
            'time' => __('app.time'),
            'type' => __('app.type'),
            'category' => __('app.attendance_category'),
        ];
    }

    public function resetForm(): void
    {
        $this->date = Jalalian::now()->format('Y/m/d');
        $this->time = now()->format('H:i');
        $this->type = 'clock_in';
        $this->category = Record::CATEGORY_WORK;
    }

    public function toRecordedAt(): \Carbon\Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->time));

        return Jalalian::fromFormat('Y/m/d', $this->date)
            ->toCarbon()
            ->setTime($hour, $minute, 0);
    }
}
