<?php

namespace App\Http\Controllers\Admin;

use App\Models\VoiceLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class VoiceLibraryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => VoiceLibrary::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:64',
            'provider' => 'required|string|max:64',
            'provider_voice_id' => 'required|string|max:128',
            'gender' => 'required|string|in:男,女,中性',
            'language' => 'required|string|max:32',
            'style' => 'nullable|string|max:64',
            'sample_url' => 'nullable|string|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $voice = VoiceLibrary::create($v->validated());
        return response()->json(['data' => $voice], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => VoiceLibrary::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'string|max:64',
            'provider' => 'string|max:64',
            'provider_voice_id' => 'string|max:128',
            'gender' => 'string|in:男,女,中性',
            'language' => 'string|max:32',
            'style' => 'nullable|string|max:64',
            'sample_url' => 'nullable|string|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $voice = VoiceLibrary::findOrFail($id);
        $voice->update($v->validated());
        return response()->json(['data' => $voice]);
    }

    public function destroy(int $id): JsonResponse
    {
        VoiceLibrary::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
