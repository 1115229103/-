<?php

namespace App\Http\Controllers\Api;

use App\Models\Work;
use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class WorkController extends Controller
{
    /**
     * List user's works.
     */
    public function index(Request $request): JsonResponse
    {
        $works = Work::where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json($works);
    }

    /**
     * Create a new work.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:256',
            'style'             => 'nullable|string|max:64',
            'target_duration_sec' => 'nullable|integer|min:10|max:7200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Check plan limits
        $planFeatures = $user->membership?->plan?->features ?? ['max_projects' => 3, 'max_duration_sec' => 60];
        $currentCount = Work::where('user_id', $user->id)->count();
        if ($currentCount >= ($planFeatures['max_projects'] ?? 3)) {
            return response()->json(['error' => 'project_limit_reached', 'message' => '项目数量已达上限，请升级套餐'], 403);
        }
        $maxDuration = $planFeatures['max_duration_sec'] ?? 60;
        if ($request->target_duration_sec && (int) $request->target_duration_sec > $maxDuration) {
            return response()->json(['error' => 'duration_limit_reached', 'message' => "当前套餐最长支持 {$maxDuration} 秒视频，请升级套餐"], 403);
        }

        $work = Work::create([
            'user_id'            => $user->id,
            'title'              => $request->title,
            'style'              => $request->style,
            'target_duration_sec' => $request->target_duration_sec,
            'status'             => 'draft',
        ]);

        return response()->json(['data' => $work], 201);
    }

    /**
     * Get work detail with all related data.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $work = Work::where('user_id', $request->user()->id)
            ->with(['script', 'characters', 'scenes', 'storyboards', 'audioTracks', 'subtitles', 'exportTasks'])
            ->findOrFail($id);

        return response()->json(['data' => $work]);
    }

    /**
     * Update work.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'               => 'nullable|string|max:256',
            'style'               => 'nullable|string|max:64',
            'target_duration_sec' => 'nullable|integer|min:10|max:7200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $planFeatures = $user->membership?->plan?->features ?? ['max_duration_sec' => 60];
        $maxDuration = $planFeatures['max_duration_sec'] ?? 60;
        if ($request->target_duration_sec && (int) $request->target_duration_sec > $maxDuration) {
            return response()->json(['error' => 'duration_limit_reached', 'message' => "当前套餐最长支持 {$maxDuration} 秒视频，请升级套餐"], 403);
        }

        $work = Work::where('user_id', $user->id)->findOrFail($id);
        $work->update($validator->validated());

        return response()->json(['data' => $work]);
    }

    /**
     * Delete work.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $work = Work::where('user_id', $request->user()->id)->findOrFail($id);
        $work->delete();

        return response()->json(null, 204);
    }

    /**
     * Start pipeline execution for a work.
     */
    public function startPipeline(Request $request, int $id): JsonResponse
    {
        $work = Work::where('user_id', $request->user()->id)->findOrFail($id);

        if (!in_array($work->status, ['draft', 'failed'])) {
            return response()->json(['error' => 'work_already_processing', 'message' => 'Work is already processing or completed'], 400);
        }

        try {
            $pipeline = app(PipelineService::class);
            $pipeline->start($work, $request->user());
        } catch (\Throwable $e) {
            $work->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json([
                'error' => 'pipeline_start_failed',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['data' => ['status' => $work->status, 'pipeline_state' => $work->pipeline_state]]);
    }

    /**
     * Get pipeline progress.
     */
    public function pipelineProgress(Request $request, int $id): JsonResponse
    {
        $work = Work::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'data' => [
                'status'       => $work->status,
                'state'        => $work->pipeline_state,
                'progress'     => $work->pipeline_progress,
                'error'        => $work->error_message,
            ],
        ]);
    }
}
