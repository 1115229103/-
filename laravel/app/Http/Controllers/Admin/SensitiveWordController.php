<?php

namespace App\Http\Controllers\Admin;

use App\Models\SensitiveWord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class SensitiveWordController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => SensitiveWord::orderBy('category')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'word' => 'required|string|max:128',
            'category' => 'required|string|in:political,adult,violence,spam,custom',
            'severity' => 'required|integer|in:1,2',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $word = SensitiveWord::create($v->validated());
        return response()->json(['data' => $word], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => SensitiveWord::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'word' => 'string|max:128',
            'category' => 'string|in:political,adult,violence,spam,custom',
            'severity' => 'integer|in:1,2',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $word = SensitiveWord::findOrFail($id);
        $word->update($v->validated());
        return response()->json(['data' => $word]);
    }

    public function destroy(int $id): JsonResponse
    {
        SensitiveWord::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
