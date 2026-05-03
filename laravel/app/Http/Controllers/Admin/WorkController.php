<?php

namespace App\Http\Controllers\Admin;

use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class WorkController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Work::with('user')->latest()->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Work::with(['user', 'script', 'characters', 'storyboards'])->findOrFail($id)]);
    }

    public function destroy(int $id): JsonResponse
    {
        Work::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
