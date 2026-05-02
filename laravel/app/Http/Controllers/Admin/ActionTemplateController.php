<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ActionTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => ActionTemplate::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:128',
            'category' => 'required|string|in:打斗,魔法,日常,追逐,情感,特效,运镜',
            'prompt_cn' => 'required|string',
            'prompt_en' => 'nullable|string',
            'tags' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $tpl = ActionTemplate::create($v->validated());
        return response()->json(['data' => $tpl], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => ActionTemplate::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'string|max:128',
            'category' => 'string|in:打斗,魔法,日常,追逐,情感,特效,运镜',
            'prompt_cn' => 'string',
            'prompt_en' => 'nullable|string',
            'tags' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $tpl = ActionTemplate::findOrFail($id);
        $tpl->update($v->validated());
        return response()->json(['data' => $tpl]);
    }

    public function destroy(int $id): JsonResponse
    {
        ActionTemplate::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
