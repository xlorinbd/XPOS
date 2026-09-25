<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AutoPurchase::class,
        Commands\DsoAlert::class,
        Commands\ResetDB::class,
        Commands\DailyAccountPdf::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('purchase:auto')->everyFiveMinutes();
        $schedule->command('dsoalert:find')->dailyAt('00:00');
        // Day-end: save every branch's Daily Account PDF and tell the accountants
        $schedule->command('kg:daily-account')->dailyAt('23:55');
        // NOTE: the demo commands reset:db (drops every table) and quote:daily (mails every user) were
        // scheduled here in the original package. They must never run on a real shop, so they are not scheduled.
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
