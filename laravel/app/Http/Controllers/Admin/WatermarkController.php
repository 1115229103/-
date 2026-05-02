<?php

namespace App\Http\Controllers\Admin;

use App\Models\WatermarkConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class WatermarkController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => WatermarkConfig::first()]);
    }

    public function update(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'type' => 'required|string|in:image,text',
            'position' => 'required|string|in:top_left,top_right,bottom_left,bottom_right,center',
            'image_url' => 'nullable|string|max:512',
            'text' => 'nullable|string|max:256',
            'text_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'opacity' => 'integer|min:0|max:100',
            'width_percent' => 'integer|min:0|max:100',
            'blind_enabled' => 'boolean',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $config = WatermarkConfig::first();
        $data = $v->validated();
        if (!$config) {
            $config = WatermarkConfig::create($data);
        } else {
            $config->update($data);
        }
        return response()->json(['data' => $config]);
    }
}
