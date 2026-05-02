<?php

namespace App\Http\Controllers\Admin;

use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PipelineStageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => PipelineStage::orderBy('sort_order')->get()]);
    }

    public function update(Request $request, string $stage): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'is_required' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer|min:0|max:255',
            'description' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $ps = PipelineStage::where('stage', $stage)->firstOrFail();
        $ps->update($v->validated());
        return response()->json(['data' => $ps]);
    }
}
