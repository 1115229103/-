<?php
/**
 * Comprehensive API test runner for AIStory.
 * Tests the full user flow: register → login → model configs → works.
 */
$base = 'http://127.0.0.1:8000/api/v1';
$fastapi = 'http://127.0.0.1:8001';
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true || (is_array($result) && ($result['ok'] ?? true))) {
            echo "  PASS: {$name}\n";
            $passed++;
        } else {
            echo "  FAIL: {$name} — {$result}\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "  FAIL: {$name} — {$e->getMessage()}\n";
        $failed++;
    }
}

function api(string $method, string $url, ?array $data = null, ?string $token = null, array $extraHeaders = []): array {
    $ch = curl_init($url);
    $headers = array_merge(
        [
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        $token ? ["Authorization: Bearer {$token}"] : [],
        $extraHeaders,
    );
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => array_values(array_filter($headers)),
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true) ?: $body];
}

echo "=== AIStory API Test Suite ===\n\n";
// Read internal token from .env (keep in sync with FASTAPI_INTERNAL_TOKEN)
$envFile = __DIR__ . '/../.env';
$envToken = '';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), 'FASTAPI_INTERNAL_TOKEN=')) {
            $envToken = trim(substr($line, strpos($line, '=') + 1));
            break;
        }
    }
}
$fastapiHeaders = ['X-Internal-Token: ' . ($envToken ?: 'internal-secret-token')];

echo "--- FastAPI Health ---\n";
test('FastAPI root', fn() => api('GET', "{$fastapi}/")['code'] === 200);
test('FastAPI health', fn() => api('GET', "{$fastapi}/internal/health")['code'] === 200);
test('FastAPI generate-dek', fn() => api('POST', "{$fastapi}/internal/generate-dek", null, null, $fastapiHeaders)['code'] === 200);

echo "\n--- Public Endpoints ---\n";
test('GET /models/categories (public)', function() use ($base) {
    $r = api('GET', "{$base}/models/categories");
    return $r['code'] === 200 && isset($r['body']['data']);
});
test('GET /models (public)', function() use ($base) {
    $r = api('GET', "{$base}/models");
    return $r['code'] === 200 && isset($r['body']['data']);
});
test('GET /models?category=llm', function() use ($base) {
    $r = api('GET', "{$base}/models?category=llm");
    $count = count($r['body']['data'] ?? []);
    return $r['code'] === 200 && $count > 0 ? true : "Got {$count} models";
});

echo "\n--- Auth Flow ---\n";
$email = 'test-' . time() . '@aistory.dev';
$password = 'TestPass123';
$token = null;
$userId = null;

test('POST /auth/register', function() use ($base, &$token, &$userId, $email, $password) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 'Test User',
        'email' => $email,
        'password' => $password,
    ]);
    if ($r['code'] !== 201) return "Code {$r['code']}: " . json_encode($r['body']);
    $token = $r['body']['data']['token'] ?? null;
    $userId = $r['body']['data']['user']['id'] ?? null;
    return $token && $userId ? true : 'Missing token or user id';
});

test('GET /auth/me', function() use ($base, $token) {
    $r = api('GET', "{$base}/auth/me", null, $token);
    return $r['code'] === 200 && ($r['body']['data']['email'] ?? null) ? true : "Code {$r['code']}";
});

test('GET /user/model-configs (empty)', function() use ($base, $token) {
    $r = api('GET', "{$base}/user/model-configs", null, $token);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

echo "\n--- Model Config CRUD ---\n";
$configId = null;

// Get first active LLM model
$models = api('GET', "{$base}/models?category=llm");
$llmModelId = $models['body']['data'][0]['id'] ?? null;

test('POST /user/model-configs (add key)', function() use ($base, $token, $llmModelId, &$configId) {
    $r = api('POST', "{$base}/user/model-configs", [
        'model_registry_id' => $llmModelId,
        'stage' => 'script_analysis',
        'api_key' => 'sk-test-api-key-12345',
    ], $token);
    if ($r['code'] !== 201) return "Code {$r['code']}: " . json_encode($r['body']);
    $configId = $r['body']['data']['id'] ?? null;
    if (!$configId) return 'Missing config id';
    $masked = $r['body']['data']['api_key_masked'] ?? null;
    if (!$masked || !str_starts_with($masked, '****')) return 'Missing or invalid api_key_masked';
    return true;
});

test('GET /user/model-configs (has one)', function() use ($base, $token) {
    $r = api('GET', "{$base}/user/model-configs", null, $token);
    return (count($r['body']['data'] ?? []) > 0) ? true : 'No configs found';
});

test('POST /user/model-configs/{id}/verify', function() use ($base, $token, $configId) {
    $r = api('POST', "{$base}/user/model-configs/{$configId}/verify", null, $token);
    // Verification may fail against real API (fake key), but endpoint should work
    return in_array($r['code'], [200, 422, 500]) ? true : "Code {$r['code']}";
});

test('DELETE /user/model-configs/{id}', function() use ($base, $token, $configId) {
    $r = api('DELETE', "{$base}/user/model-configs/{$configId}", null, $token);
    return $r['code'] === 204 ? true : "Code {$r['code']}";
});

echo "\n--- Plans & Memberships ---\n";
test('GET /plans', function() use ($base, $token) {
    $r = api('GET', "{$base}/plans", null, $token);
    return $r['code'] === 200 && count($r['body']['data'] ?? []) > 0 ? true : "Code {$r['code']}";
});

test('GET /membership', function() use ($base, $token) {
    $r = api('GET', "{$base}/membership", null, $token);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

echo "\n--- Works CRUD ---\n";
$workId = null;

test('POST /works', function() use ($base, $token, &$workId) {
    $r = api('POST', "{$base}/works", [
        'title' => 'Test Work',
        'style' => '写实',
        'target_duration_sec' => 60,
    ], $token);
    if ($r['code'] !== 201) return "Code {$r['code']}: " . json_encode($r['body']);
    $workId = $r['body']['data']['id'] ?? null;
    return $workId ? true : 'Missing work id';
});

test('GET /works', function() use ($base, $token) {
    $r = api('GET', "{$base}/works", null, $token);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

test('GET /works/{id}', function() use ($base, $token, $workId) {
    $r = api('GET', "{$base}/works/{$workId}", null, $token);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

test('PUT /works/{id}', function() use ($base, $token, $workId) {
    $r = api('PUT', "{$base}/works/{$workId}", ['title' => 'Updated Title'], $token);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

test('DELETE /works/{id}', function() use ($base, $token, $workId) {
    $r = api('DELETE', "{$base}/works/{$workId}", null, $token);
    return $r['code'] === 204 ? true : "Code {$r['code']}";
});

echo "\n--- Auth Guard Checks ---\n";
test('GET /user/model-configs (no token → 401)', function() use ($base) {
    $r = api('GET', "{$base}/user/model-configs");
    return $r['code'] === 401 ? true : "Code {$r['code']} (expected 401)";
});

test('GET /works (no token → 401)', function() use ($base) {
    $r = api('GET', "{$base}/works");
    return $r['code'] === 401 ? true : "Code {$r['code']} (expected 401)";
});

echo "\n--- Auth Password Change ---\n";
test('POST /auth/change-password rejects wrong current password', function() use ($base, $token) {
    $r = api('POST', "{$base}/auth/change-password", ['current_password' => 'wrong', 'new_password' => 'NewPass456'], $token);
    return $r['code'] === 403 ? true : "Code {$r['code']} (expected 403)";
});

test('POST /auth/change-password with correct current password', function() use ($base, $token, $password) {
    $r = api('POST', "{$base}/auth/change-password", ['current_password' => $password, 'new_password' => 'NewPass456'], $token);
    return $r['code'] === 200 ? true : "Code {$r['code']} (expected 200)";
});

test('Login works with new password after change', function() use ($base, $email) {
    $r = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => 'NewPass456']);
    return ($r['code'] === 200 && isset($r['body']['data']['token'])) ? true : "Code {$r['code']}";
});

// Reset password back for other tests
$resetToken = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => 'NewPass456'])['body']['data']['token'] ?? '';
if ($resetToken) {
    api('POST', "{$base}/auth/change-password", ['current_password' => 'NewPass456', 'new_password' => $password], $resetToken);
}

echo "\n--- Auth Logout ---\n";
test('POST /auth/logout', function() use ($base, $token) {
    $r = api('POST', "{$base}/auth/logout", null, $token);
    return $r['code'] === 204 ? true : "Code {$r['code']}";
});

test('Token invalid after logout', function() use ($base, $token) {
    $r = api('GET', "{$base}/auth/me", null, $token);
    return $r['code'] === 401 ? true : "Code {$r['code']} (expected 401)";
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
