<?php

namespace App\Http\Controllers\Api;

use App\Models\ModelRegistry;
use App\Models\PipelineStage;
use App\Models\UserModelConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ModelController extends Controller
{
    /**
     * Get all model categories with their stages.
     */
    public function categories(): JsonResponse
    {
        $stages = PipelineStage::enabled()->orderBy('sort_order')->get()
            ->groupBy('category')
            ->map(fn($group) => $group->map(fn($s) => [
                'stage'       => $s->stage,
                'name'        => $s->name,
                'is_required' => $s->is_required,
                'description' => $s->description,
            ]));

        return response()->json(['data' => $stages]);
    }

    /**
     * List available models for a category (user-facing, for model selection).
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->get('category');

        $models = ModelRegistry::active()
            ->when($category, fn($q) => $q->byCategory($category))
            ->orderBy('sort_order')
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'category'       => $m->category,
                'model_name'     => $m->model_name,
                'display_name'   => $m->display_name,
                'provider'       => $m->provider,
                'api_type'       => $m->api_type,
                'status'         => $m->status,
                'description'    => $m->description,
                'docs_url'       => $m->docs_url,
                'logo_url'       => $m->logo_url,
                'required_fields' => $m->required_fields,
            ]);

        return response()->json(['data' => $models]);
    }

    /**
     * Get user's configured models (key masked).
     */
    public function myConfigs(Request $request): JsonResponse
    {
        $configs = UserModelConfig::where('user_id', $request->user()->id)
            ->with('model')
            ->get()
            ->map(fn($c) => [
                'id'                => $c->id,
                'category'          => $c->category,
                'stage'             => $c->stage,
                'model_registry_id' => $c->model_registry_id,
                'model_display_name' => $c->model?->display_name,
                'provider'          => $c->model?->provider,
                'api_type'          => $c->model?->api_type,
                'api_key_masked'    => $c->masked_key,
                'custom_params'     => $c->custom_params,
                'priority'          => $c->priority,
                'status'            => $c->status,
                'last_verified_at'  => $c->last_verified_at,
            ]);

        return response()->json(['data' => $configs]);
    }

    /**
     * Save a model configuration (key encrypted via FastAPI).
     */
    public function storeConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_registry_id' => 'required|exists:model_registry,id',
            'stage'             => 'required|string|max:32',
            'api_key'           => 'required|string|max:512',
            'additional_fields' => 'nullable|array',
            'custom_params'     => 'nullable|array',
            'priority'          => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $model = ModelRegistry::findOrFail($request->model_registry_id);
        $rawKey = $request->api_key;

        // Encrypt the API key via FastAPI
        $encryptedKey = $this->encryptKeyViaFastAPI($user->wrapped_dek, $rawKey);
        if (!$encryptedKey) {
            return response()->json(['error' => 'key_encryption_failed', 'message' => 'Key encryption failed'], 500);
        }

        $config = UserModelConfig::updateOrCreate(
            [
                'user_id'           => $user->id,
                'category'          => $model->category,
                'stage'             => $request->stage,
                'model_registry_id' => $request->model_registry_id,
            ],
            [
                'api_key'       => $encryptedKey,
                'custom_params' => $request->custom_params,
                'priority'      => $request->priority ?? 0,
                'status'        => 'active',
            ]
        );

        return response()->json([
            'data' => [
                'id'                 => $config->id,
                'stage'              => $config->stage,
                'status'             => $config->status,
                'category'           => $config->category,
                'api_key_masked'     => '****' . substr($rawKey, -4),
                'model_display_name' => $model->display_name,
                'provider'           => $model->provider,
                'api_type'           => $model->api_type,
            ],
        ], 201);
    }

    /**
     * Update a model configuration.
     */
    public function updateConfig(Request $request, int $id): JsonResponse
    {
        $config = UserModelConfig::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'api_key'       => 'nullable|string|max:512',
            'custom_params' => 'nullable|array',
            'priority'      => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updatedKey = null;
        if ($request->has('api_key')) {
            $updatedKey = $request->api_key;
            $config->api_key = $this->encryptKeyViaFastAPI($request->user()->wrapped_dek, $updatedKey);
        }
        if ($request->has('custom_params')) {
            $config->custom_params = $request->custom_params;
        }
        if ($request->has('priority')) {
            $config->priority = $request->priority;
        }
        $config->save();

        $data = ['id' => $config->id, 'status' => $config->status];
        if ($updatedKey !== null) {
            $data['api_key_masked'] = '****' . substr($updatedKey, -4);
        }
        return response()->json(['data' => $data]);
    }

    /**
     * Delete a model configuration.
     */
    public function deleteConfig(Request $request, int $id): JsonResponse
    {
        $config = UserModelConfig::where('user_id', $request->user()->id)->findOrFail($id);
        $config->delete();

        return response()->json(null, 204);
    }

    /**
     * Verify a model configuration's API key.
     */
    public function verifyConfig(Request $request, int $id): JsonResponse
    {
        $config = UserModelConfig::where('user_id', $request->user()->id)
            ->with('model')
            ->findOrFail($id);

        $model = $config->model;
        $additionalFields = $request->additional_fields ?? [];

        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => config('services.fastapi.internal_token'),
            ])->post(config('services.fastapi.url') . '/internal/verify-key', [
                'wrapped_dek'    => $request->user()->wrapped_dek,
                'api_type'       => $model->api_type,
                'base_url'       => $model->base_url,
                'encrypted_key'  => $config->api_key,
                'access_key_id'  => $additionalFields['access_key_id'] ?? null,
                'region'         => $additionalFields['region'] ?? null,
            ]);

            $valid = $response->json('valid', false);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'key_verification_unavailable', 'message' => 'Key verification service unavailable'], 503);
        }
        $config->update([
            'status'           => $valid ? 'active' : 'error',
            'last_verified_at' => now(),
        ]);

        return response()->json(['data' => ['valid' => $valid, 'status' => $config->status]]);
    }

    /**
     * Encrypt a user's API key via the FastAPI internal service.
     */
    private function encryptKeyViaFastAPI(string $wrappedDek, string $apiKey): ?string
    {
        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => config('services.fastapi.internal_token'),
            ])->post(config('services.fastapi.url') . '/internal/encrypt-key', [
                'wrapped_dek' => $wrappedDek,
                'api_key'     => $apiKey,
            ]);

            return $response->json('encrypted_key');
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
