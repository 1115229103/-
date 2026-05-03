<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Expire memberships past their end date
        $schedule->command('membership:expire')->everyFiveMinutes();

        // Clean up failed pipeline works older than 7 days
        $schedule->command('works:cleanup-failed')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
