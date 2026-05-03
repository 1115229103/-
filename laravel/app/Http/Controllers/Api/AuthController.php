<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user. Also generates User DEK via FastAPI.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate User DEK via FastAPI
        try {
            $dekResponse = Http::withHeaders([
                'X-Internal-Token' => config('services.fastapi.internal_token'),
            ])->post(config('services.fastapi.url') . '/internal/generate-dek');

            $wrappedDek = $dekResponse->json('wrapped_dek');
            if (!$wrappedDek) {
                return response()->json(['error' => 'Key generation failed'], 500);
            }
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Key generation service unavailable'], 503);
        }

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'wrapped_dek' => $wrappedDek,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => $user->only(['id', 'name', 'email']),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login and return Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => '邮箱或密码错误'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user'  => $user->only(['id', 'name', 'email', 'avatar_url']),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(null, 204);
    }

    /**
     * Change password for authenticated user.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => '当前密码错误'], 403);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['data' => ['status' => 'ok']]);
    }

    /**
     * Get current user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'data' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'avatar_url'      => $user->avatar_url,
                'membership'      => $user->membership?->plan?->only(['name', 'tier']),
                'model_config_count' => $user->modelConfigs()->count(),
            ],
        ]);
    }
}
