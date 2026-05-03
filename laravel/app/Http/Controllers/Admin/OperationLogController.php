<?php

namespace App\Http\Controllers\Admin;

use App\Models\OperationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OperationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = OperationLog::with('user')
            ->when($request->module, fn($q) => $q->where('module', $request->module))
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
