<?php

namespace App\Http\Controllers\Admin;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Backup::latest()->get()]);
    }

    public function create(BackupService $service): JsonResponse
    {
        $backup = $service->create('db');

        return response()->json(['data' => $backup], 201);
    }

    public function download(int $id): BinaryFileResponse
    {
        $backup = Backup::findOrFail($id);

        if ($backup->status !== 'completed' || !$backup->file_path) {
            abort(404);
        }

        $fullPath = storage_path('backups') . DIRECTORY_SEPARATOR . $backup->file_path;

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->download($fullPath);
    }

    public function destroy(int $id): JsonResponse
    {
        $backup = Backup::findOrFail($id);

        if ($backup->file_path) {
            $fullPath = storage_path('backups') . DIRECTORY_SEPARATOR . $backup->file_path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $backup->delete();

        return response()->json(null, 204);
    }
}
