<?php

namespace App\Http\Controllers\Admin;

use App\Models\PromptTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PromptTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $stage = $request->get('stage');
        $templates = $stage
            ? PromptTemplate::where('stage', $stage)->get()
            : PromptTemplate::all();
        return response()->json(['data' => $templates]);
    }

    public function update(Request $request, string $stage): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'required|string',
            'output_schema' => 'nullable|array',
            'variables' => 'nullable|array',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $template = PromptTemplate::where('stage', $stage)->firstOrFail();
        $template->update($v->validated());
        return response()->json(['data' => $template]);
    }
}
