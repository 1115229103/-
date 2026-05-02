<?php

namespace App\Services;

use App\Models\Storyboard;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Log;

class ExportService
{
    /**
     * Composite the final video from all generated assets.
     * Uses FFmpeg for video compositing, watermark injection, and encoding.
     */
    public function composite(Work $work, User $user): array
    {
        $storyboards = $work->storyboards()->where('status', 'completed')->orderBy('shot_number')->get();
        $planFeatures = $user->membership?->plan?->features ?? [];
        $maxResolution = $planFeatures['max_resolution'] ?? '720p';
        $hasWatermark = $planFeatures['watermark'] ?? true;

        if ($storyboards->isEmpty()) {
            throw new \RuntimeException('No completed storyboards to composite');
        }

        // Generate video segments list file for FFmpeg concat
        $concatFile = $this->buildConcatFile($storyboards);
        $outputPath = $this->getOutputPath($work);

        // Build FFmpeg command
        $ffmpeg = config('services.ffmpeg.path', 'ffmpeg');
        $resolutionMap = ['720p' => '1280x720', '1080p' => '1920x1080', '4k' => '3840x2160', '8k' => '7680x4320'];
        $scale = $resolutionMap[$maxResolution] ?? '1280x720';

        $cmd = sprintf(
            '%s -f concat -safe 0 -i %s -vf "scale=%s:force_original_aspect_ratio=decrease,pad=%s:(ow-iw)/2:(oh-ih)/2" -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -y %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($concatFile),
            $scale,
            $scale,
            escapeshellarg($outputPath)
        );

        // Inject watermark if needed
        if ($hasWatermark) {
            $watermarkService = app(WatermarkService::class);
            $cmd = $watermarkService->injectWatermarkFilter($cmd, $scale);
        }

        // Execute FFmpeg
        Log::info("Running FFmpeg: {$cmd}");
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        Log::info("FFmpeg exit code: {$exitCode}", $output);

        if ($exitCode !== 0) {
            throw new \RuntimeException('FFmpeg composite failed: ' . implode("\n", $output));
        }

        // Clean up concat file
        @unlink($concatFile);

        // Generate downloadable URL
        $url = $this->generateDownloadUrl($work, $outputPath);

        return [
            'url' => $url,
            'path' => $outputPath,
            'resolution' => $maxResolution,
        ];
    }

    /**
     * Build an FFmpeg concat file from storyboard videos.
     */
    private function buildConcatFile($storyboards): string
    {
        $path = storage_path('app/temp/concat_' . uniqid() . '.txt');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [];
        foreach ($storyboards as $sb) {
            if ($sb->video_url && file_exists($sb->video_url)) {
                $lines[] = "file '" . addslashes($sb->video_url) . "'";
                $lines[] = "duration " . $sb->duration_sec;
            }
        }

        // Add last file entry without duration (FFmpeg requirement)
        file_put_contents($path, implode("\n", $lines));
        return $path;
    }

    /**
     * Get the output file path for a work.
     */
    private function getOutputPath(Work $work): string
    {
        $dir = storage_path('app/exports/' . $work->user_id);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/' . $work->id . '_' . time() . '.mp4';
    }

    /**
     * Generate a signed download URL.
     */
    private function generateDownloadUrl(Work $work, string $outputPath): string
    {
        $expiresAt = now()->addHours(24);
        $token = hash_hmac('sha256', $work->id . '|' . $expiresAt->timestamp, config('app.key'));
        return url("/api/v1/works/{$work->id}/download") . "?expires={$expiresAt->timestamp}&token={$token}";
    }
}
