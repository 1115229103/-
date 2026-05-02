<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Work;
use App\Services\PipelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunPipelineStageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $workId,
        public int $userId,
        public string $stage,
    ) {}

    public function handle(PipelineService $pipeline): void
    {
        $work = Work::findOrFail($this->workId);
        $user = User::findOrFail($this->userId);

        Log::info("Running pipeline stage: {$this->stage} for work {$this->workId}");

        try {
            $result = $pipeline->runStage($work, $user, $this->stage);
            $this->recordSuccess($work, $result);
            $this->dispatchNextStage($work);
        } catch (\Exception $e) {
            Log::error("Stage {$this->stage} failed: {$e->getMessage()}");

            if ($this->attempts() >= $this->tries) {
                $work->update([
                    'status'        => 'failed',
                    'error_message' => "{$this->stage}: {$e->getMessage()}",
                ]);
            } else {
                $this->release($this->backoff * $this->attempts());
            }
        }
    }

    private function recordSuccess(Work $work, array $result): void
    {
        $progress = $work->pipeline_progress ?? [];
        $progress[$this->stage] = [
            'status'     => 'completed',
            'completed_at' => now()->toIso8601String(),
            'data'       => $result['data'] ?? null,
        ];

        $work->update([
            'pipeline_state'    => $this->stage,
            'pipeline_progress' => $progress,
        ]);
    }

    private function dispatchNextStage(Work $work): void
    {
        $nextStage = match ($this->stage) {
            'script_analysis' => 'storyboard',
            'storyboard'      => 'continuation',
            'continuation'    => 'image_gen',
            'image_gen'       => 'consistency',
            'consistency'     => 'image_enhance',
            'image_enhance'   => 'image2video',
            'image2video'     => 'video_enhance',
            'video_enhance'   => 'tts',
            'tts'             => 'music',
            'music'           => 'asr',
            'asr'             => 'moderation',
            'moderation'      => 'compositing',
            default           => null,
        };

        if ($nextStage === 'compositing') {
            $work->update(['status' => 'compositing']);
            dispatch(new \App\Jobs\ComposeVideoJob($this->workId, $this->userId));
        } elseif ($nextStage) {
            $work->update(['pipeline_state' => $nextStage]);
            dispatch(new self($this->workId, $this->userId, $nextStage));
        }
    }
}
