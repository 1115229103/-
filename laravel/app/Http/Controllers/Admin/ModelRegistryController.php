<?php

namespace App\Http\Controllers\Admin;

use App\Models\ModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ModelRegistryController extends Controller
{
    private const VALID_CATEGORIES = [
        'llm', 'image_gen', 'consistency', 'image_enhance', 'image2video',
        'video_enhance', 'tts', 'music', 'asr', 'moderation',
    ];

    /**
     * List all registered models (admin view, includes inactive).
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $models = ModelRegistry::when($category, fn($q) => $q->byCategory($category))
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $models]);
    }

    /**
     * Store a new model registration.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category'        => ['required', 'string', 'in:' . implode(',', self::VALID_CATEGORIES)],
            'model_name'      => 'required|string|max:128',
            'display_name'    => 'required|string|max:128',
            'provider'        => 'required|string|max:64',
            'api_type'        => 'required|string|max:32',
            'base_url'        => 'required|string|max:512',
            'request_path'    => 'nullable|string|max:256',
            'default_params'  => 'nullable|array',
            'required_fields' => 'nullable|array',
            'description'     => 'nullable|string',
            'docs_url'        => 'nullable|string|max:512',
            'logo_url'        => 'nullable|string|max:512',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $model = ModelRegistry::create($validator->validated());

        $this->logOperation($request, 'model_registry', 'create', $model->id, null, $model->toArray());

        return response()->json(['data' => $model], 201);
    }

    /**
     * Update a model registration.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $model = ModelRegistry::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category'        => ['sometimes', 'string', 'in:' . implode(',', self::VALID_CATEGORIES)],
            'model_name'      => 'sometimes|string|max:128',
            'display_name'    => 'sometimes|string|max:128',
            'provider'        => 'sometimes|string|max:64',
            'api_type'        => 'sometimes|string|max:32',
            'base_url'        => 'sometimes|string|max:512',
            'request_path'    => 'nullable|string|max:256',
            'default_params'  => 'nullable|array',
            'required_fields' => 'nullable|array',
            'description'     => 'nullable|string',
            'docs_url'        => 'nullable|string|max:512',
            'logo_url'        => 'nullable|string|max:512',
            'sort_order'      => 'nullable|integer|min:0',
            'status'          => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $old = $model->toArray();
        $model->update($validator->validated());

        $this->logOperation($request, 'model_registry', 'update', $model->id, $old, $model->fresh()->toArray());

        return response()->json(['data' => $model]);
    }

    /**
     * Delete a model registration.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $model = ModelRegistry::findOrFail($id);
        $this->logOperation($request, 'model_registry', 'delete', $id, $model->toArray(), null);
        $model->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle model active/inactive status.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $model = ModelRegistry::findOrFail($id);
        $newStatus = $model->status === 'active' ? 'inactive' : 'active';
        $oldStatus = $model->status;
        $model->update(['status' => $newStatus]);

        $this->logOperation($request, 'model_registry', 'status', $id, ['status' => $oldStatus], ['status' => $newStatus]);

        return response()->json(['data' => ['id' => $id, 'status' => $newStatus]]);
    }

    /**
     * Batch update sort order.
     */
    public function updateSort(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:model_registry,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->items as $item) {
            ModelRegistry::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Log an admin operation.
     */
    private function logOperation(Request $request, string $module, string $action, int $targetId, $old, $new): void
    {
        \App\Models\OperationLog::create([
            'user_id'     => $request->user()?->id,
            'module'      => $module,
            'action'      => $action,
            'target_type' => 'model_registry',
            'target_id'   => $targetId,
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => $request->ip(),
        ]);
    }
}
