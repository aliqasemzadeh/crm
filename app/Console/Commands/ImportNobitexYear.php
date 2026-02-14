<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class ImportNobitexYear extends Command
{
    protected $signature = 'rates:import-nobitex {year}';
    protected $description = 'Import daily USDT IRR rates from Nobitex for a given Jalali year';

    public function handle()
    {
        $jalaliYear = (int) $this->argument('year');

        $this->info("Importing Jalali year: {$jalaliYear}");

        // ساخت شروع سال
        $jalaliStart = Jalalian::fromFormat('Y-m-d', "{$jalaliYear}-01-01");

        // ساخت پایان سال (بدون استفاده از copy)
        $jalaliEnd = Jalalian::fromFormat('Y-m-d', "{$jalaliYear}-01-01")
            ->addYears(1)
            ->subDays(1);

        $startGregorian = $jalaliStart->toCarbon()->startOfDay();
        $endGregorian   = $jalaliEnd->toCarbon()->endOfDay();

        // اگر سال جاری است، تا امروز محدود کن
        if ($endGregorian->greaterThan(now())) {
            $endGregorian = now()->endOfDay();
        }

        $from = $startGregorian->setTime(12,0)->timestamp;
        $to   = $endGregorian->setTime(12,0)->timestamp;

        $this->info("Gregorian range: {$startGregorian->toDateString()} → {$endGregorian->toDateString()}");

        $response = Http::timeout(60)->get(
            'https://apiv2.nobitex.ir/market/udf/history',
            [
                'symbol' => 'USDTIRT',
                'resolution' => 'D',
                'from' => $from,
                'to' => $to,
            ]
        );

        if (!$response->ok()) {
            $this->error("API error: " . $response->status());
            return;
        }

        $data = $response->json();

        if (!isset($data['s']) || $data['s'] !== 'ok') {
            $this->error("Invalid response from Nobitex");
            return;
        }

        $count = 0;

        foreach ($data['t'] as $index => $timestamp) {

            $date = Carbon::createFromTimestamp($timestamp)->toDateString();
            $closeIrt = $data['c'][$index]; // تومان
            $rateIrr = round($closeIrt * 10); // تبدیل به ریال

            DB::table('currency_rates')->updateOrInsert(
                [
                    'currency_code' => 'USDT',
                    'rate_date' => $date,
                ],
                [
                    'rate_irr' => $rateIrr,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $count++;
        }

        $this->info("✅ Imported {$count} days successfully.");
    }
}
