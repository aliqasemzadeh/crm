<?php

namespace App\Livewire\Panels\Accounting\Tax;

use App\Exports\TaxReportExport;
use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    public $startDate;
    public $endDate;
    public $saleType;
    public $fiscalYear;

    protected $queryString = ['startDate', 'endDate', 'saleType', 'fiscalYear'];

    public function mount()
    {
        $fiscalYears = $this->getFiscalYears();
        if ($fiscalYears->isNotEmpty()) {
            $this->fiscalYear = $fiscalYears->first()->FiscalYearId;
        }

        // Current date: 2026-04-18 -> 1405-01-29
        // Quarter 1: 1405/01/01 to 1405/03/31
        $this->startDate = '1405/01/01';
        $this->endDate = '1405/03/31';
    }

    public function getFiscalYears()
    {
        return DB::connection('sqlsrv')->table('FMK.FiscalYear')
            ->orderBy('StartDate', 'desc')
            ->get();
    }

    public function export()
    {
        return Excel::download(new TaxReportExport($this->getReportData()), 'tax-report.xlsx');
    }

    public function getReportData()
    {
        if ($this->startDate && $this->endDate) {
            $startStr = $this->persianToGregorian($this->startDate);
            $endStr = $this->persianToGregorian($this->endDate);

            if ($startStr && $endStr) {
                $start = \Carbon\Carbon::parse($startStr);
                $end = \Carbon\Carbon::parse($endStr);

                if ($start->diffInDays($end) > 100) {
                    return collect([]);
                }
            }
        }

        $query = DB::connection('sqlsrv')->table('SLS.Invoice as inv')
            ->join('GNR.Party as p', 'inv.CustomerPartyRef', '=', 'p.PartyId')
            ->select(
                'p.EconomicCode',
                'p.IdentificationCode',
                'p.Name',
                'p.LastName',
                DB::raw('COUNT(inv.InvoiceId) as invoices_count'),
                DB::raw('SUM(inv.Price) as total_price'),
                DB::raw('SUM(inv.Tax) as total_tax'),
                DB::raw('SUM(inv.NetPrice) as total_net_price')
            )
            ->groupBy('p.EconomicCode', 'p.IdentificationCode', 'p.Name', 'p.LastName');

        if ($this->fiscalYear) {
            $query->where('inv.FiscalYearRef', $this->fiscalYear);
        }

        if ($this->startDate) {
            $startDate = $this->persianToGregorian($this->startDate);
            if ($startDate) {
                $query->where('inv.Date', '>=', $startDate);
            }
        }

        if ($this->endDate) {
            $endDate = $this->persianToGregorian($this->endDate);
            if ($endDate) {
                $query->where('inv.Date', '<=', $endDate);
            }
        }

        if ($this->saleType) {
            $query->where('inv.SaleTypeRef', $this->saleType);
        }

        return $query->get();
    }

    protected function persianToGregorian($persianDate)
    {
        if (!$persianDate) return null;

        // Assuming format YYYY/MM/DD
        $parts = explode('/', $persianDate);
        if (count($parts) !== 3) return null;

        $jy = (int)$parts[0];
        $jm = (int)$parts[1];
        $jd = (int)$parts[2];

        if ($jy === 0 || $jm === 0 || $jd === 0) return null;

        $gy = ($jy <= 979) ? 621 : 1600;
        $jy -= ($jy <= 979) ? 0 : 979;
        $days = (365 * $jy) + (int)($jy / 33) * 8 + (int)((($jy % 33) + 3) / 4) + 78 + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy += 400 * (int)($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * (int)(--$days / 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    public function render()
    {
        return view('livewire.panels.accounting.tax.index', [
            'reportData' => $this->getReportData()
        ])->layout('layouts.panels.accounting');
    }
}
