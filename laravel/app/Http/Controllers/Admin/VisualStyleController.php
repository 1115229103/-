<?php

namespace App\Http\Controllers\Admin;

use App\Models\VisualStyle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class VisualStyleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => VisualStyle::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:64',
            'category' => 'required|string|in:image,video,both',
            'prompt_keyword' => 'required|string|max:256',
            'preview_url' => 'nullable|string|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $style = VisualStyle::create($v->validated());
        return response()->json(['data' => $style], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => VisualStyle::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'string|max:64',
            'category' => 'string|in:image,video,both',
            'prompt_keyword' => 'string|max:256',
            'preview_url' => 'nullable|string|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $style = VisualStyle::findOrFail($id);
        $style->update($v->validated());
        return response()->json(['data' => $style]);
    }

    public function destroy(int $id): JsonResponse
    {
        VisualStyle::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
