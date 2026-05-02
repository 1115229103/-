<?php

namespace App\Http\Controllers\Admin;

use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Template::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:128',
            'category' => 'required|string|in:script,storyboard,style',
            'content' => 'required|string',
            'preview_url' => 'nullable|string|max:512',
            'is_premium' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $tpl = Template::create($v->validated());
        return response()->json(['data' => $tpl], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Template::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'string|max:128',
            'category' => 'string|in:script,storyboard,style',
            'content' => 'string',
            'preview_url' => 'nullable|string|max:512',
            'is_premium' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $tpl = Template::findOrFail($id);
        $tpl->update($v->validated());
        return response()->json(['data' => $tpl]);
    }

    public function destroy(int $id): JsonResponse
    {
        Template::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
