<?php

namespace App\Http\Controllers\Admin;

use App\Models\Backup;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class BackupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Backup::latest()->get()]);
    }

    public function create(): JsonResponse
    {
        $backup = Backup::create([
            'type'   => 'full',
            'status' => 'pending',
        ]);
        // In production: dispatch backup job

        return response()->json(['data' => $backup], 201);
    }
}
