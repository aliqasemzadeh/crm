<?php

namespace App\Livewire\Panels\Accounting\Concerns;

use Morilog\Jalali\Jalalian;

trait SelectsJalaliChequeMonth
{
    public function bootJalaliChequeMonth(): void
    {
        $now = Jalalian::now();

        if ($this->year < 1300 || $this->year > 1500) {
            $this->year = $now->getYear();
        }

        if ($this->month < 1 || $this->month > 12) {
            $this->month = $now->getMonth();
        }
    }

    public function selectMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            return;
        }

        $this->month = $month;
        $this->resetPage();
    }

    public function previousYear(): void
    {
        $this->year--;
        $this->resetPage();
    }

    public function nextYear(): void
    {
        $this->year++;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    protected function gregorianBounds(int $startMonth, int $endMonth): array
    {
        $start = (new Jalalian($this->year, $startMonth, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($this->year, $endMonth, 1))->getMonthDays();
        $end = (new Jalalian($this->year, $endMonth, $days))->toCarbon()->endOfDay();

        return [$start, $end];
    }
}
