<?php

namespace App\Console\Commands;

use App\Models\Work;
use Illuminate\Console\Command;

class CleanupFailedWorksCommand extends Command
{
    protected $signature = 'works:cleanup-failed';
    protected $description = 'Delete failed pipeline works older than 7 days';

    public function handle(): int
    {
        $count = Work::where('status', 'failed')
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();

        if ($count > 0) {
            $this->info("Cleaned up {$count} failed work(s).");
        }

        return self::SUCCESS;
    }
}
