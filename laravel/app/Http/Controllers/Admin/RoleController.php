<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select(['id', 'name', 'email', 'role', 'created_at'])
            ->orderBy('role')
            ->orderBy('id')
            ->paginate(30);
        return response()->json(['data' => $users]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user = User::findOrFail($id);
        $user->update(['role' => $data['role']]);
        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role'])]);
    }
}
