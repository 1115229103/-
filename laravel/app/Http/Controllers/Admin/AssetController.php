<?php

namespace App\Http\Controllers\Admin;

use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Asset::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:128',
            'type' => 'required|string|in:bgm,sfx,image,video',
            'file_url' => 'required|url:http,https|max:512',
            'mime_type' => 'nullable|string|max:64',
            'file_size_bytes' => 'nullable|integer|min:0',
            'duration_sec' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $asset = Asset::create($v->validated());
        return response()->json(['data' => $asset], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Asset::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'string|max:128',
            'type' => 'string|in:bgm,sfx,image,video',
            'file_url' => 'url:http,https|max:512',
            'mime_type' => 'nullable|string|max:64',
            'file_size_bytes' => 'nullable|integer|min:0',
            'duration_sec' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $asset = Asset::findOrFail($id);
        $asset->update($v->validated());
        return response()->json(['data' => $asset]);
    }

    public function destroy(int $id): JsonResponse
    {
        Asset::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
