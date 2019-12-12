<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Jobs\Test::class,
        Jobs\AutoFreeUser::class,
        Jobs\ComputeSignCount::class,
        Jobs\ComputeUserDailyStat::class,
        Jobs\ClearSearchRepeatData::class,
        Jobs\GetNewsBangumi::class,
        Jobs\GetHottestBangumi::class,
        Jobs\UpdateIdolMarketPrice::class,
        Jobs\SaveBangumiScore::class,
        Jobs\UpdateBangumiRank::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('AutoFreeUser')->everyFiveMinutes();
        $schedule->command('ComputeSignCount')->dailyAt('00:30');
        $schedule->command('ComputeUserDailyStat')->dailyAt('00:01');
        $schedule->command('ClearSearchRepeatData')->dailyAt('05:00');
        $schedule->command('GetHottestBangumi')->everyMinute()->withoutOverlapping();
        $schedule->command('GetNewsBangumi')->hourly()->withoutOverlapping();
        $schedule->command('UpdateIdolMarketPrice')->hourly()->withoutOverlapping();
//        $schedule->command('SaveBangumiScore')->everyMinute()->withoutOverlapping();
        $schedule->command('Test')->everyMinute()->withoutOverlapping();
        $schedule->command('UpdateBangumiRank')->daily();
    }
}
