<?php

namespace App\Http\Controllers\Admin;

use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->get('status', 'pending_review');
        $works = Work::with('user')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);
        return response()->json(['data' => $works]);
    }

    public function approve(int $id): JsonResponse
    {
        $work = Work::findOrFail($id);
        $work->update(['status' => 'completed']);
        return response()->json(['data' => $work]);
    }

    public function reject(int $id): JsonResponse
    {
        $work = Work::findOrFail($id);
        $work->update(['status' => 'rejected']);
        return response()->json(['data' => $work]);
    }
}
