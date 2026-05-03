<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Always return success to prevent email enumeration
        if (!$user) {
            return response()->json(['message' => 'If that email exists, a reset token has been generated.']);
        }

        // Delete old tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Generate a new token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        // Build reset URL (frontend uses this to submit new password)
        $resetUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/user-app/reset-password?token=' . urlencode($token) . '&email=' . urlencode($request->email);

        // Send password reset email via configured mailer
        try {
            \Illuminate\Support\Facades\Mail::to($user)->send(new \App\Mail\PasswordResetMail($token, $resetUrl));
        } catch (\Throwable $e) {
            report($e);
            // Mail sending failed — still return token in non-production for testing
        }

        $response = ['message' => 'If that email exists, a reset token has been generated.'];
        // Return token in non-production envs or when APP_DEBUG=true
        if (!app()->environment('production') || config('app.debug')) {
            $response['token'] = $token;
        }

        return response()->json($response);
    }
}
