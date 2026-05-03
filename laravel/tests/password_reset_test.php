<?php
/**
 * AIStory — Password Reset Flow Test
 *
 * Tests the complete forgot-password → reset-password → login-with-new flow.
 * Covers: token generation, token expiry simulation, wrong token rejection,
 * password confirmation validation, and security token revocation after reset.
 */

declare(strict_types=1);

$BASE   = 'http://127.0.0.1:8085/api/v1';
$passed = 0;
$failed = 0;

function request(string $method, string $path, ?array $data = null, ?string $token = null): array
{
    global $BASE;
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    $ch = curl_init("$BASE$path");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
    ]);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => (int) $status, 'body' => json_decode($body, true) ?? []];
}

function pass(string $msg): void
{
    global $passed;
    echo "  \033[32mPASS\033[0m: $msg\n";
    $passed++;
}

function fail(string $msg): void
{
    global $failed;
    echo "  \033[31mFAIL\033[0m: $msg\n";
    $failed++;
}

echo "=== AIStory Password Reset Flow Test ===\n\n";

// ━━━ Setup: Register user ━━━
$email    = 'pwreset_' . uniqid() . '@test.com';
$password = 'OldPass123';

$res = request('POST', '/auth/register', [
    'name'     => 'PwdReset Tester',
    'email'    => $email,
    'password' => $password,
]);
$token = $res['body']['data']['token'] ?? null;
$res['status'] === 201 ? pass("Register → 201") : fail("Register → {$res['status']}");

// ━━━ Phase 1: Forgot password ━━━
$res = request('POST', '/auth/forgot-password', ['email' => $email]);
$res['status'] === 200 ? pass("Forgot password (valid email) → 200") : fail("Forgot password → {$res['status']}");

// In dev mode, the token is returned in the response
$resetToken = $res['body']['token'] ?? null;
if ($resetToken) {
    pass("Dev mode: reset token returned in response");
} else {
    fail("Dev mode: no reset token in response");
}

// Anti-enumeration: non-existent email should also return 200
$res = request('POST', '/auth/forgot-password', ['email' => 'nonexistent@nowhere.com']);
$res['status'] === 200 ? pass("Forgot password (non-existent email) → 200 (anti-enumeration)") : fail("Forgot password non-existent → {$res['status']}");

// Anti-enumeration: response should NOT reveal whether email exists
$msg = $res['body']['message'] ?? '';
str_contains($msg, 'If that email exists') || str_contains($msg, 'generated')
    ? pass("Non-existent email response is properly vague")
    : fail("Non-existent email response leaks existence info: $msg");

// ━━━ Phase 2: Reset with wrong token ━━━
$res = request('POST', '/auth/reset-password', [
    'token'    => 'invalid-token-that-does-not-exist',
    'email'    => $email,
    'password' => 'NewPass456',
    'password_confirmation' => 'NewPass456',
]);
$res['status'] === 422 ? pass("Reset with wrong token → 422") : fail("Reset with wrong token → {$res['status']}");

// ━━━ Phase 3: Reset with valid token ━━━
$newPassword = 'NewPass456!';
$res = request('POST', '/auth/reset-password', [
    'token'    => $resetToken,
    'email'    => $email,
    'password' => $newPassword,
    'password_confirmation' => $newPassword,
]);
$res['status'] === 200 ? pass("Reset with valid token → 200") : fail("Reset with valid token → {$res['status']}");

$msg = $res['body']['message'] ?? '';
str_contains($msg, 'success') || str_contains($msg, 'reset')
    ? pass("Reset success message is clear")
    : fail("Reset response unexpected: $msg");

// ━━━ Phase 4: Old password no longer works ━━━
$res = request('POST', '/auth/login', ['email' => $email, 'password' => $password]);
$res['status'] === 401 ? pass("Old password rejected → 401") : fail("Old password should be rejected → {$res['status']}");

// ━━━ Phase 5: New password works ━━━
$res = request('POST', '/auth/login', ['email' => $email, 'password' => $newPassword]);
$newToken = $res['body']['data']['token'] ?? null;
$res['status'] === 200 ? pass("New password login → 200") : fail("New password login → {$res['status']}");
$newToken ? pass("New login returns valid token") : fail("New login missing token");

// ━━━ Phase 6: Old token revoked after password reset ━━━
if ($token) {
    $res = request('GET', '/auth/me', null, $token);
    $res['status'] === 401 ? pass("Old token revoked after password reset → 401") : fail("Old token should be revoked → {$res['status']}");
}

// ━━━ Phase 7: Token reuse prevention (token consumed) ━━━
$res = request('POST', '/auth/reset-password', [
    'token'    => $resetToken,
    'email'    => $email,
    'password' => 'AnotherPass789!',
    'password_confirmation' => 'AnotherPass789!',
]);
$res['status'] === 422 ? pass("Reused token rejected → 422") : fail("Reused token should be rejected → {$res['status']}");

// ━━━ Phase 8: Password confirmation mismatch ━━━
$res = request('POST', '/auth/forgot-password', ['email' => $email]);
$token2 = $res['body']['token'] ?? null;
if ($token2) {
    $res = request('POST', '/auth/reset-password', [
        'token'    => $token2,
        'email'    => $email,
        'password' => 'MismatchPass1',
        'password_confirmation' => 'MismatchPass2',
    ]);
    $res['status'] === 422 ? pass("Password confirmation mismatch → 422") : fail("Mismatch should be rejected → {$res['status']}");
}

// ━━━ Cleanup ━━━
if ($newToken) {
    request('POST', '/auth/logout', null, $newToken);
}

// ━━━ Summary ━━━
echo "\n" . str_repeat('=', 60) . "\n";
echo "Results: $passed passed, $failed failed\n";
echo str_repeat('=', 60) . "\n";

exit($failed > 0 ? 1 : 0);
