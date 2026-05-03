<?php

namespace App\Console\Commands;

use App\Models\Membership;
use Illuminate\Console\Command;

class MembershipExpireCommand extends Command
{
    protected $signature = 'membership:expire';
    protected $description = 'Expire memberships past their end date';

    public function handle(): int
    {
        $count = Membership::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        if ($count > 0) {
            $this->info("Expired {$count} membership(s).");
        }

        return self::SUCCESS;
    }
}
