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

        // Prune expired Sanctum tokens (default: >24h old)
        $schedule->command('sanctum:prune-expired')->daily();

        // Prune expired password reset tokens
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
