<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::with('membership.plan')->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => User::with(['membership.plan', 'modelConfigs.model'])->findOrFail($id)]);
    }
}
