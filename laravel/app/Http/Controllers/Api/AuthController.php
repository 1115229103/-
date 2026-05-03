<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Membership;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
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
                return response()->json(['error' => 'key_generation_failed', 'message' => 'Key generation failed'], 500);
            }
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'key_generation_unavailable', 'message' => 'Key generation service unavailable'], 503);
        }

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'wrapped_dek' => $wrappedDek,
        ]);

        // Auto-assign free plan membership
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan && !Membership::where('user_id', $user->id)->exists()) {
            Membership::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'status'  => 'active',
                'starts_at' => now(),
            ]);
        }

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

        // Account-level brute force protection: 5 failed attempts = 15 min lockout
        // Skip for localhost (consistent with RateLimitMiddleware)
        $isLocal = in_array($request->ip(), ['127.0.0.1', '::1']);

        if (!$isLocal) {
            $lockKey = 'login_lockout:' . sha1($request->email);
            $attempts = Cache::get($lockKey, 0);
            if ($attempts >= 5) {
                return response()->json([
                    'error'   => 'account_locked',
                    'message' => '登录尝试次数过多，请15分钟后再试',
                ], 429);
            }
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            if (!$isLocal) {
                Cache::put($lockKey, $attempts + 1, 900);
            }
            return response()->json(['error' => 'invalid_credentials', 'message' => '邮箱或密码错误'], 401);
        }

        // Clear lockout on successful login
        if (!$isLocal) {
            Cache::forget($lockKey);
        }

        $user->tokens()->delete();

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
            return response()->json(['error' => 'wrong_current_password', 'message' => '当前密码错误'], 403);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['data' => ['status' => 'ok']]);
    }

    /**
     * Update profile (name, avatar_url).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|string|max:255',
            'avatar_url' => 'sometimes|nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'avatar_url']));

        return response()->json([
            'data' => $user->only(['id', 'name', 'email', 'avatar_url']),
        ]);
    }

    /**
     * Delete own account (GDPR right to erasure). Requires password confirmation.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'wrong_password', 'message' => '密码错误，无法注销账号'], 403);
        }

        // Revoke all tokens first
        $user->tokens()->delete();

        // Soft-delete the user (preserves data integrity for their works)
        $user->delete();

        return response()->json(['data' => ['message' => '账号已注销']]);
    }

    /**
     * Get current user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('membership.plan');
        return response()->json([
            'data' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'role'            => $user->role,
                'avatar_url'      => $user->avatar_url,
                'created_at'      => $user->created_at,
                'membership'      => $user->membership?->plan?->only(['name', 'tier']),
                'model_config_count' => $user->modelConfigs()->count(),
            ],
        ]);
    }
}
