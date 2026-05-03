<?php

namespace App\Services;

use App\Models\PipelineStage;
use App\Models\User;
use App\Models\UserModelConfig;
use App\Models\Work;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipelineService
{
    /**
     * Start the full pipeline for a work.
     * Dispatches jobs stage by stage.
     */
    public function start(Work $work, User $user): void
    {
        $work->update([
            'status'         => 'parsing',
            'pipeline_state' => 'script_analysis',
            'pipeline_progress' => [],
            'error_message'  => null,
        ]);

        // Dispatch the first stage
        dispatch(new \App\Jobs\RunPipelineStageJob($work->id, $user->id, 'script_analysis'));
    }

    /**
     * Run a single pipeline stage.
     */
    public function runStage(Work $work, User $user, string $stage): array
    {
        $pipelineStage = PipelineStage::where('stage', $stage)->first();
        if (!$pipelineStage || !$pipelineStage->is_enabled) {
            Log::info("Stage {$stage} is disabled, skipping");
            return ['status' => 'skipped'];
        }

        // Find user's model config for this stage
        $config = UserModelConfig::where('user_id', $user->id)
            ->where('category', $pipelineStage->category)
            ->where('stage', $stage)
            ->with('model')
            ->active()
            ->orderBy('priority')
            ->first();

        if (!$config) {
            if ($pipelineStage->is_required) {
                throw new \RuntimeException("No model configured for required stage: {$stage}");
            }
            Log::info("No model config for optional stage {$stage}, skipping");
            return ['status' => 'skipped'];
        }

        $model = $config->model;

        // Build stage input from work data
        $stageInput = $this->buildStageInput($work, $stage);

        // Call FastAPI to execute the stage
        $response = $this->callFastAPI($user, $config, $stage, $stageInput);

        if ($response['status'] === 'failed') {
            throw new \RuntimeException("Stage {$stage} failed: " . ($response['error'] ?? 'unknown'));
        }

        return $response;
    }

    /**
     * Build the stage-specific input from work data.
     */
    private function buildStageInput(Work $work, string $stage): array
    {
        return match ($stage) {
            'script_analysis' => [
                'params' => [
                    'script_content' => $work->script?->content ?? '',
                ],
            ],
            'storyboard' => [
                'params' => [
                    'characters_json'  => $work->script?->parsed_data['characters'] ?? [],
                    'scenes_json'      => $work->script?->parsed_data['scenes'] ?? [],
                    'plot_units_json'  => $work->script?->parsed_data['plot_units'] ?? [],
                    'target_duration'  => $work->target_duration_sec ?? 60,
                    'style_preference' => $work->style ?? '写实',
                ],
            ],
            'image_gen' => [
                'params' => $this->buildImageGenParams($work),
            ],
            'image2video' => [
                'params' => $this->buildImage2VideoParams($work),
                'is_async' => true,
                'poll_timeout' => 900,
            ],
            default => ['params' => []],
        };
    }

    private function buildImageGenParams(Work $work): array
    {
        $storyboards = $work->storyboards()->where('status', 'pending')->get();
        $prompts = [];

        foreach ($storyboards as $sb) {
            $scene = $sb->scene;
            $prompts[] = [
                'storyboard_id' => $sb->id,
                'prompt'        => implode('，', array_filter([
                    $work->style . '风格',
                    $sb->shot_type . '镜头',
                    $scene?->description ?? '',
                    $sb->action_description,
                    $sb->emotion . '氛围',
                    ($scene?->time_of_day === '夜' ? '暗光' : '明亮') . '光线',
                    '高画质，细节丰富',
                ])),
            ];
        }

        return ['prompts' => $prompts];
    }

    private function buildImage2VideoParams(Work $work): array
    {
        $storyboards = $work->storyboards()
            ->where('status', 'completed')
            ->whereNotNull('image_url')
            ->get();

        $tasks = [];
        foreach ($storyboards as $sb) {
            $scene = $sb->scene;
            $characters = $sb->characters_in_frame ?? [];
            $charName = $characters[0] ?? '';
            $character = $work->characters()->where('name', $charName)->first();

            $tasks[] = [
                'storyboard_id' => $sb->id,
                'image_url'     => $sb->image_url,
                'prompt'        => implode('，', array_filter([
                    $charName . ($character ? "（{$character->appearance}）" : ''),
                    $sb->action_description,
                    '镜头' . $sb->shot_type,
                    $sb->camera_movement . '运镜',
                    $sb->emotion . '氛围',
                    $scene?->description . '背景',
                    '动作流畅自然，画面稳定，高画质',
                    $work->style . '风格',
                ])),
            ];
        }

        return ['tasks' => $tasks];
    }

    /**
     * Call the FastAPI internal endpoint to run a stage.
     */
    private function callFastAPI(User $user, UserModelConfig $config, string $stage, array $input): array
    {
        return Http::withHeaders([
            'X-Internal-Token' => config('services.fastapi.internal_token'),
        ])->retry(3, 100)
          ->post(config('services.fastapi.url') . '/internal/run-stage', [
            'user_id'      => $user->id,
            'stage'        => $stage,
            'wrapped_dek'  => $user->wrapped_dek,
            'model_config' => [
                'api_type'      => $config->model->api_type,
                'base_url'      => $config->model->base_url,
                'request_path'  => $config->model->request_path,
                'api_key'       => $config->api_key,
                'default_params' => $config->model->default_params,
                'custom_params'  => $config->custom_params,
            ],
            'stage_input' => $input,
        ])->throw()->json();
    }
}
