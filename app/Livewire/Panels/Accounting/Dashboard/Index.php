<?php

namespace App\Livewire\Panels\Accounting\Dashboard;

use App\Models\Sepidar\FMK\FiscalYear;
use App\Services\Accounting\AccountingDashboardService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

class Index extends Component
{
    public int|string $fiscalYearRef;

    public int $selectedMonth;

    public function mount(): void
    {
        $this->fiscalYearRef = (int) config('sepidar.FiscalYearRef');
        $this->selectedMonth = $this->defaultMonthForFiscalYear($this->fiscalYearRef);
    }

    public function updatedFiscalYearRef(): void
    {
        $this->selectedMonth = $this->defaultMonthForFiscalYear($this->fiscalYearRef);
        unset($this->stats);
    }

    public function updatedSelectedMonth(): void
    {
        $this->selectedMonth = max(1, min(12, (int) $this->selectedMonth));
        unset($this->stats);
    }

    public function reload(): void
    {
        AccountingDashboardService::clearCache($this->fiscalYearRef);
        unset($this->stats);
        Flux::toast(__('app.dashboard_data_reloaded'));
    }

    public static function clearCache(int|string|null $fiscalYearRef = null): void
    {
        AccountingDashboardService::clearCache($fiscalYearRef);
    }

    #[Computed]
    public function fiscalYears()
    {
        return FiscalYear::query()->orderByDesc('FiscalYearID')->get();
    }

    #[Computed]
    public function stats(): array
    {
        return app(AccountingDashboardService::class)->stats(
            $this->fiscalYearRef,
            $this->selectedMonth
        );
    }

    private function defaultMonthForFiscalYear(int|string $fiscalYearRef): int
    {
        $fiscalYear = FiscalYear::query()->find($fiscalYearRef);
        $now = Jalalian::now();

        if ($fiscalYear && (int) $fiscalYear->Title === $now->getYear()) {
            return $now->getMonth();
        }

        return 1;
    }

    #[Layout('layouts.panels.accounting')]
    public function render()
    {
        return view('livewire.panels.accounting.dashboard.index');
    }
}
