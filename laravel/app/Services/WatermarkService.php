<?php

namespace App\Services;

use App\Models\WatermarkConfig;
use Illuminate\Support\Facades\Cache;

class WatermarkService
{
    /**
     * Get the current watermark configuration.
     */
    public function getConfig(): ?WatermarkConfig
    {
        return Cache::remember('watermark_config', 3600, fn() => WatermarkConfig::first());
    }

    /**
     * Inject watermark filter into an FFmpeg command string.
     * Uses FFmpeg overlay filter for visible watermarks.
     */
    public function injectWatermarkFilter(string $ffmpegCmd, string $resolution): string
    {
        $config = $this->getConfig();
        if (!$config || $config->type === 'blind') {
            return $ffmpegCmd;
        }

        $overlayFilter = $this->buildOverlayFilter($config, $resolution);

        // Inject watermark overlay before the output
        // Find the output file (-y output.mp4) and inject the overlay filter before it
        $ffmpegCmd = preg_replace(
            '/(-vf\s+")([^"]*)(")/',
            '$1$2,' . $overlayFilter . '$3',
            $ffmpegCmd
        );

        return $ffmpegCmd;
    }

    /**
     * Build FFmpeg overlay filter for watermark.
     */
    private function buildOverlayFilter(WatermarkConfig $config, string $resolution): string
    {
        $widthPercent = $config->width_percent / 100;
        [$w, $h] = explode('x', $resolution);

        $overlayW = intval((int)$w * $widthPercent);
        $overlayH = -1; // Auto-scale height

        // Position coordinates
        $position = match ($config->position) {
            'top_left'     => '10:10',
            'top_right'    => 'main_w-overlay_w-10:10',
            'bottom_left'  => '10:main_h-overlay_h-10',
            'bottom_right' => 'main_w-overlay_w-10:main_h-overlay_h-10',
            'center'       => '(main_w-overlay_w)/2:(main_h-overlay_h)/2',
            default        => 'main_w-overlay_w-10:main_h-overlay_h-10',
        };

        // If text watermark (no image)
        if (!$config->image_url && $config->text) {
            $fontSize = max(16, intval($overlayW / strlen($config->text ?? 'AIStory')));
            $textColor = ltrim($config->text_color ?? '#FFFFFF', '#');
            return sprintf(
                "drawtext=text='%s':fontsize=%d:fontcolor=%s@%0.2f:x=%s:y=%s",
                addslashes($config->text),
                $fontSize,
                $textColor,
                $config->opacity / 100,
                $position,
                $position
            );
        }

        // Image watermark
        $opacity = $config->opacity / 100;
        $imagePath = $config->image_url;
        return sprintf(
            "movie='%s',scale=%d:-1,format=rgba,colorchannelmixer=aa=%0.2f [wm]; [0:v][wm] overlay=%s",
            addslashes($imagePath),
            $overlayW,
            $opacity,
            $position
        );
    }
}
