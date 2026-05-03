<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Banner::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:128',
            'image_url' => 'required|url:http,https|max:512',
            'link_url' => 'nullable|url:http,https|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $banner = Banner::create($v->validated());
        return response()->json(['data' => $banner], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Banner::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'title' => 'string|max:128',
            'image_url' => 'url:http,https|max:512',
            'link_url' => 'nullable|url:http,https|max:512',
            'sort_order' => 'integer|min:0',
            'status' => 'string|in:active,inactive',
        ]);
        if ($v->fails()) return response()->json(['errors' => $v->errors()], 422);
        $banner = Banner::findOrFail($id);
        $banner->update($v->validated());
        return response()->json(['data' => $banner]);
    }

    public function destroy(int $id): JsonResponse
    {
        Banner::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
