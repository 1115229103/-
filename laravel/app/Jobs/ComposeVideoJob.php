<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Work;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComposeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public int $workId,
        public int $userId,
    ) {}

    public function handle(ExportService $export): void
    {
        $work = Work::findOrFail($this->workId);
        $user = User::findOrFail($this->userId);

        Log::info("Compositing video for work {$this->workId}");

        try {
            $result = $export->composite($work, $user);
            $work->update([
                'status'         => 'completed',
                'pipeline_state' => 'completed',
                'pipeline_progress' => array_merge(
                    $work->pipeline_progress ?? [],
                    ['compositing' => ['status' => 'completed', 'output_url' => $result['url'] ?? null]]
                ),
            ]);
        } catch (\Exception $e) {
            Log::error("Compositing failed for work {$this->workId}: {$e->getMessage()}");
            if ($this->attempts() >= $this->tries) {
                $work->update([
                    'status'        => 'failed',
                    'error_message' => "compositing: {$e->getMessage()}",
                ]);
            } else {
                $this->release(30);
            }
        }
    }
}
