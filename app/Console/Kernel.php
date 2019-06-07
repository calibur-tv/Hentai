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
        Jobs\CronFreeUser::class,
        Jobs\ComputeSignCount::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('CronFreeUser')->everyFiveMinutes();
        $schedule->command('ComputeSignCount')->dailyAt('00:30');
        $schedule->command('Test')->everyMinute()->withoutOverlapping();
    }
}
