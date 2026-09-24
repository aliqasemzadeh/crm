<?php

namespace App\Livewire\Forms\Hr;

use App\Models\Calender\Day;
use App\Models\Hr\DayRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Morilog\Jalali\Jalalian;

class DayRecordForm extends Form
{
    public string $date = '';

    public string $type = DayRecord::TYPE_LEAVE;

    public string $user_note = '';

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{2}\/\d{2}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        $carbon = Jalalian::fromFormat('Y/m/d', (string) $value)->toCarbon()->startOfDay();
                    } catch (\Throwable) {
                        $fail(__('validation.date', ['attribute' => __('app.date')]));

                        return;
                    }

                    if ($this->type === DayRecord::TYPE_LEAVE && Day::isNonWorkingDay($carbon)) {
                        $fail(__('app.hr_day_record_leave_on_holiday'));
                    }
                },
            ],
            'type' => ['required', 'in:'.implode(',', DayRecord::TYPES)],
            'user_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'date' => __('app.date'),
            'type' => __('app.type'),
            'user_note' => __('app.description'),
        ];
    }

    public function resetForm(): void
    {
        $this->date = Jalalian::now()->format('Y/m/d');
        $this->type = DayRecord::TYPE_LEAVE;
        $this->user_note = '';
    }

    public function toDateString(): string
    {
        return Jalalian::fromFormat('Y/m/d', $this->date)->toCarbon()->toDateString();
    }

    public function assertUniqueForUser(int $userId): void
    {
        $exists = DayRecord::query()
            ->where('user_id', $userId)
            ->whereDate('date', $this->toDateString())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'form.date' => __('app.hr_day_record_already_exists'),
            ]);
        }
    }
}
