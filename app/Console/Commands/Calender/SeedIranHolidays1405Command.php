<?php

namespace App\Console\Commands\Calender;

use App\Models\Calender\Day;
use Illuminate\Console\Command;
use Morilog\Jalali\Jalalian;

class SeedIranHolidays1405Command extends Command
{
    protected $signature = 'app:calender:seed-iran-holidays-1405';

    protected $description = 'Seed official Iran holidays for Jalali year 1405';

    public function handle(): int
    {
        $holidays = [
            ['1405/01/01', 'عید فطر و آغاز نوروز'],
            ['1405/01/02', 'تعطیل عید فطر و عید نوروز'],
            ['1405/01/03', 'عید نوروز'],
            ['1405/01/04', 'عید نوروز'],
            ['1405/01/12', 'روز جمهوری اسلامی ایران'],
            ['1405/01/13', 'روز طبیعت'],
            ['1405/01/25', 'شهادت امام جعفر صادق (ع)'],
            ['1405/03/06', 'عید قربان'],
            ['1405/03/14', 'عید غدیر خم و رحلت امام خمینی'],
            ['1405/03/15', 'قیام ۱۵ خرداد'],
            ['1405/04/03', 'تاسوعای حسینی'],
            ['1405/04/04', 'عاشورای حسینی'],
            ['1405/05/13', 'اربعین حسینی'],
            ['1405/05/21', 'رحلت پیامبر و شهادت امام حسن مجتبی (ع)'],
            ['1405/05/22', 'شهادت امام رضا (ع)'],
            ['1405/05/30', 'شهادت امام حسن عسکری (ع)'],
            ['1405/06/08', 'ولادت پیامبر و امام صادق (ع)'],
            ['1405/08/22', 'شهادت حضرت فاطمه زهرا (س)'],
            ['1405/10/02', 'ولادت امام علی (ع)'],
            ['1405/10/16', 'مبعث پیامبر'],
            ['1405/11/04', 'ولادت حضرت قائم (عج) — نیمه شعبان'],
            ['1405/11/22', 'پیروزی انقلاب اسلامی'],
            ['1405/12/09', 'شهادت امام علی (ع)'],
            ['1405/12/19', 'عید فطر'],
            ['1405/12/20', 'تعطیل عید فطر'],
            ['1405/12/29', 'روز ملی شدن صنعت نفت'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($holidays as [$jalaliDate, $title]) {
            $gregorian = Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon()->toDateString();

            $day = Day::query()->updateOrCreate(
                ['date' => $gregorian],
                [
                    'title' => $title,
                    'source' => 'official',
                ]
            );

            if ($day->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            $this->line("{$jalaliDate} ({$gregorian}) — {$title}");
        }

        $this->info("Done. Created: {$created}, Updated: {$updated}");

        return self::SUCCESS;
    }
}
