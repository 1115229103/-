<?php

namespace App\Console\Commands;

use App\Models\Work;
use Illuminate\Console\Command;

class CleanupFailedWorksCommand extends Command
{
    protected $signature = 'works:cleanup-failed';
    protected $description = 'Force-delete failed pipeline works older than 7 days and soft-deleted works older than 30 days';

    public function handle(): int
    {
        $count = 0;

        $count += Work::where('status', 'failed')
            ->where('updated_at', '<', now()->subDays(7))
            ->forceDelete();

        $count += Work::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(30))
            ->forceDelete();

        if ($count > 0) {
            $this->info("Cleaned up {$count} work(s).");
        }

        return self::SUCCESS;
    }
}
