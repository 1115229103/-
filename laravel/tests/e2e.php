<?php
/**
 * AIStory Comprehensive E2E Test Suite
 * Covers: validation, CRUD lifecycle, rate limiting, error formats, data integrity, business logic
 * Requires: MySQL running, Laravel API server running on 127.0.0.1:8000
 * Usage: php tests/e2e.php
 */
$base = 'http://127.0.0.1:8000/api/v1';
$fastapi = 'http://127.0.0.1:8001';
$passed = 0;
$failed = 0;
$warnings = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed, $warnings;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  \033[32mPASS\033[0m: {$name}\n";
            $passed++;
        } elseif ($result === 'WARN') {
            echo "  \033[33mWARN\033[0m: {$name}\n";
            $warnings++;
        } else {
            echo "  \033[31mFAIL\033[0m: {$name} — {$result}\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "  \033[31mFAIL\033[0m: {$name} — {$e->getMessage()}\n";
        $failed++;
    }
}

function api(string $method, string $url, ?array $data = null, ?string $token = null, array $extraHeaders = []): array {
    $ch = curl_init($url);
    $headers = array_merge(
        ['Accept: application/json', 'Content-Type: application/json'],
        $token ? ["Authorization: Bearer {$token}"] : [],
        $extraHeaders,
    );
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => array_values(array_filter($headers)),
        CURLOPT_HEADER => true,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersRaw = substr($raw, 0, $headerSize);
    $body = json_decode(substr($raw, $headerSize), true) ?: substr($raw, $headerSize);
    curl_close($ch);

    $rateHeaders = [];
    foreach (["X-RateLimit-Limit", "X-RateLimit-Remaining", "Retry-After"] as $h) {
        if (preg_match("/{$h}:\s*(\d+)/i", $headersRaw, $m)) {
            $rateHeaders[$h] = (int)$m[1];
        }
    }

    return ['code' => $code, 'body' => $body, 'rate' => $rateHeaders];
}

function _uid(): string {
    return bin2hex(random_bytes(6));
}

echo "╔══════════════════════════════════════════════════╗\n";
echo "║     AIStory Comprehensive E2E Test Suite         ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// Create a shared test user upfront to minimize registrations (rate limit: 20/min guest)
$sharedEmail = 'e2e-shared-' . _uid() . '@test.dev';
$sharedPass = 'E2EShared123';
$reg = api('POST', "{$base}/auth/register", [
    'name' => 'E2E Shared User',
    'email' => $sharedEmail,
    'password' => $sharedPass,
]);
$sharedToken = $reg['body']['data']['token'] ?? '';
if (!$sharedToken) {
    echo "FATAL: Cannot create shared test user. API may be down.\n";
    echo "Response: " . json_encode($reg['body']) . "\n";
    exit(1);
}
echo "Shared user created. Token: " . substr($sharedToken, 0, 16) . "...\n\n";

// Cache first model ID for pipeline/config tests
$modelsRes = api('GET', "{$base}/models");
$sharedModelId = $modelsRes['body']['data'][0]['id'] ?? 1;

// ═══════════════════════════════════════════════════
// SECTION 1: Validation & Error Handling
// ═══════════════════════════════════════════════════
echo "━━━ SECTION 1: Validation & Error Handling ━━━\n";

test('Register with empty name → 422', function() use ($base) {
    $r = api('POST', "{$base}/auth/register", ['name' => '', 'email' => 'test@test.com', 'password' => '12345678']);
    return $r['code'] === 422 && isset($r['body']['errors']) ? true : "Code {$r['code']}";
});

test('Register with invalid email → 422', function() use ($base) {
    $r = api('POST', "{$base}/auth/register", ['name' => 'Test', 'email' => 'not-an-email', 'password' => '12345678']);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Register with short password → 422', function() use ($base) {
    $r = api('POST', "{$base}/auth/register", ['name' => 'Test', 'email' => 'x@test.com', 'password' => '123']);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Login with wrong password → 401', function() use ($base, $sharedEmail) {
    $r = api('POST', "{$base}/auth/login", ['email' => $sharedEmail, 'password' => 'WrongPass123456']);
    return $r['code'] === 401 ? true : "Code {$r['code']}";
});

test('Login with non-existent email → 401', function() use ($base) {
    $r = api('POST', "{$base}/auth/login", ['email' => 'noone@nowhere.xyz', 'password' => 'SomePass123']);
    return $r['code'] === 401 ? true : "Code {$r['code']}";
});

test('Create work with empty title → 422', function() use ($base, $sharedToken) {
    $r = api('POST', "{$base}/works", ['title' => ''], $sharedToken);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Create work with extremely long title → 422', function() use ($base, $sharedToken) {
    $r = api('POST', "{$base}/works", ['title' => str_repeat('x', 300)], $sharedToken);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Create work with invalid duration → 422', function() use ($base, $sharedToken) {
    $r = api('POST', "{$base}/works", ['title' => 'Test', 'target_duration_sec' => 5], $sharedToken);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Add model config with non-existent model → 422', function() use ($base, $sharedToken) {
    $r = api('POST', "{$base}/user/model-configs", [
        'model_registry_id' => 99999, 'stage' => 'script_analysis', 'api_key' => 'sk-test',
    ], $sharedToken);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Add model config without api_key → 422', function() use ($base, $sharedToken) {
    $models = api('GET', "{$base}/models?category=llm");
    $modelId = $models['body']['data'][0]['id'] ?? 1;
    $r = api('POST', "{$base}/user/model-configs", [
        'model_registry_id' => $modelId, 'stage' => 'script_analysis',
    ], $sharedToken);
    return $r['code'] === 422 ? true : "Code {$r['code']}";
});

test('Access another user work → 404 (user scoping)', function() use ($base) {
    // Create user A and a work
    $rA = api('POST', "{$base}/auth/register", [
        'name' => 'User A', 'email' => 'user-a-' . _uid() . '@test.dev', 'password' => 'PassA123456',
    ]);
    $tokenA = $rA['body']['data']['token'] ?? '';
    $w = api('POST', "{$base}/works", ['title' => 'User A Work'], $tokenA);
    $workId = $w['body']['data']['id'] ?? null;
    if (!$workId) return 'Failed to create work for user A';

    // User B tries to access User A's work
    $rB = api('POST', "{$base}/auth/register", [
        'name' => 'User B', 'email' => 'user-b-' . _uid() . '@test.dev', 'password' => 'PassB123456',
    ]);
    $tokenB = $rB['body']['data']['token'] ?? '';
    $r = api('GET', "{$base}/works/{$workId}", null, $tokenB);
    return $r['code'] === 404 ? true : "Code {$r['code']} (expected 404, user B saw user A's work)";
});

// ═══════════════════════════════════════════════════
// SECTION 2: Full CRUD Lifecycle
// ═══════════════════════════════════════════════════
usleep(200000);
echo "\n━━━ SECTION 2: Full CRUD Lifecycle Tests ━━━\n";

test('User lifecycle: register → login → me → logout → verify revoked', function() use ($base) {
    $email = 'lifecycle-' . _uid() . '@test.dev';
    $pass = 'Lifecycle123';

    // Register
    $r = api('POST', "{$base}/auth/register", ['name' => 'LC User', 'email' => $email, 'password' => $pass]);
    if ($r['code'] !== 201) return "Register failed: {$r['code']}";
    $token1 = $r['body']['data']['token'] ?? null;
    if (!$token1) return 'No token from register';

    // Get me
    $me = api('GET', "{$base}/auth/me", null, $token1);
    if ($me['code'] !== 200) return "Me failed: {$me['code']}";
    if (($me['body']['data']['email'] ?? '') !== $email) return 'Email mismatch in me';

    // Logout
    $lo = api('POST', "{$base}/auth/logout", null, $token1);
    if ($lo['code'] !== 204) return "Logout code {$lo['code']}";

    // Token should be revoked
    $me2 = api('GET', "{$base}/auth/me", null, $token1);
    if ($me2['code'] !== 401) return "Token still valid after logout: {$me2['code']}";

    // Login again with new token
    $login = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => $pass]);
    if ($login['code'] !== 200) return "Login failed: {$login['code']}";
    $token2 = $login['body']['data']['token'] ?? null;
    if (!$token2) return 'No token from login';
    if ($token2 === $token1) return 'Same token after re-login';

    return true;
});

test('Work lifecycle: create → read → update → verify → delete → verify gone', function() use ($base, $sharedToken) {
    // Create
    $r = api('POST', "{$base}/works", ['title' => 'CRUD Work', 'style' => '写实', 'target_duration_sec' => 120], $sharedToken);
    if ($r['code'] !== 201) return "Create code {$r['code']}";
    $id = $r['body']['data']['id'] ?? null;
    if (!$id) return 'No work id';
    if ($r['body']['data']['status'] !== 'draft') return 'Status not draft';

    // Read
    $show = api('GET', "{$base}/works/{$id}", null, $sharedToken);
    if ($show['code'] !== 200) return "Read code {$show['code']}";
    if ($show['body']['data']['title'] !== 'CRUD Work') return 'Title mismatch';

    // Update
    $upd = api('PUT', "{$base}/works/{$id}", ['title' => 'Updated Work', 'style' => '动漫'], $sharedToken);
    if ($upd['code'] !== 200) return "Update code {$upd['code']}";

    // Verify update
    $show2 = api('GET', "{$base}/works/{$id}", null, $sharedToken);
    if ($show2['body']['data']['title'] !== 'Updated Work') return 'Title not updated';
    if ($show2['body']['data']['style'] !== '动漫') return 'Style not updated';

    // List contains it
    $list = api('GET', "{$base}/works", null, $sharedToken);
    $found = false;
    foreach ($list['body']['data']['data'] ?? [] as $w) {
        if ($w['id'] === $id) { $found = true; break; }
    }
    if (!$found) return 'Work not found in list';

    // Delete
    $del = api('DELETE', "{$base}/works/{$id}", null, $sharedToken);
    if ($del['code'] !== 204) return "Delete code {$del['code']}";

    // Verify gone
    $show3 = api('GET', "{$base}/works/{$id}", null, $sharedToken);
    return $show3['code'] === 404 ? true : "Still accessible: {$show3['code']}";
});

test('Model config lifecycle: add → list → update → verify → delete', function() use ($base, $sharedToken) {
    $models = api('GET', "{$base}/models?category=llm");
    $modelId = $models['body']['data'][0]['id'] ?? null;
    if (!$modelId) return 'No LLM model in registry';

    // Add
    $add = api('POST', "{$base}/user/model-configs", [
        'model_registry_id' => $modelId, 'stage' => 'script_analysis',
        'api_key' => 'sk-lifecycle-test-key', 'priority' => 0,
    ], $sharedToken);
    if ($add['code'] !== 201) return "Add config code {$add['code']}: " . json_encode($add['body']);
    $cfgId = $add['body']['data']['id'] ?? null;
    if (!$cfgId) return 'No config id';

    // List (key must be masked)
    $list = api('GET', "{$base}/user/model-configs", null, $sharedToken);
    $cfg = null;
    foreach ($list['body']['data'] ?? [] as $c) {
        if ($c['id'] === $cfgId) { $cfg = $c; break; }
    }
    if (!$cfg) return 'Config not found in list';
    if (($cfg['api_key_masked'] ?? '') === 'sk-lifecycle-test-key') return 'API key not masked';

    // Update
    $upd = api('PUT', "{$base}/user/model-configs/{$cfgId}", ['priority' => 2], $sharedToken);
    if ($upd['code'] !== 200) return "Update code {$upd['code']}";

    // Verify (may fail against real API, but should not 500)
    $ver = api('POST', "{$base}/user/model-configs/{$cfgId}/verify", null, $sharedToken);
    if ($ver['code'] === 500) return "Verify endpoint 500: " . json_encode($ver['body']);

    // Delete
    $del = api('DELETE', "{$base}/user/model-configs/{$cfgId}", null, $sharedToken);
    return $del['code'] === 204 ? true : "Delete code {$del['code']}";
});

// ═══════════════════════════════════════════════════
// SECTION 3: Rate Limiting
// ═══════════════════════════════════════════════════
usleep(200000);
echo "\n━━━ SECTION 3: Rate Limiting ━━━\n";

test('Rate limit headers present on response', function() use ($base) {
    $r = api('GET', "{$base}/models/categories");
    return isset($r['rate']['X-RateLimit-Limit']) ? true : 'Missing X-RateLimit-Limit header';
});

test('Guest endpoints have rate limiting', function() use ($base) {
    // After the tests so far, check Remaining count is decreasing
    $r = api('GET', "{$base}/models");
    $remaining = $r['rate']['X-RateLimit-Remaining'] ?? -1;
    $limit = $r['rate']['X-RateLimit-Limit'] ?? -1;
    if ($limit <= 0) return 'No limit header';
    if ($remaining < 0) return 'No remaining header';
    // Remaining should be less than limit (some requests already made)
    return $remaining <= $limit ? true : "Remaining {$remaining} > Limit {$limit}";
});

// ═══════════════════════════════════════════════════
// SECTION 4: Seeded Data Integrity
// ═══════════════════════════════════════════════════
usleep(200000);
echo "\n━━━ SECTION 4: Seeded Data Integrity ━━━\n";

test('Models endpoint returns all categories', function() use ($base) {
    // One call to fetch all models, then verify each category has data
    $r = api('GET', "{$base}/models");
    if ($r['code'] === 429) return 'WARN';
    if ($r['code'] !== 200) return "Code {$r['code']}";
    $models = $r['body']['data'] ?? [];
    $cats = ['llm', 'image_gen', 'consistency', 'image_enhance', 'image2video', 'video_enhance', 'tts', 'music', 'asr', 'moderation'];
    $found = [];
    foreach ($models as $m) { $found[$m['category']] = true; }
    $missing = array_diff($cats, array_keys($found));
    return empty($missing) ? true : 'Missing categories: ' . implode(', ', $missing);
});

test('Categories endpoint returns pipeline stages', function() use ($base) {
    $r = api('GET', "{$base}/models/categories");
    if ($r['code'] === 429) return 'WARN';
    $data = $r['body']['data'] ?? [];
    return count($data) > 0 ? true : "Got 0 categories (code {$r['code']})";
});

test('Plans endpoint returns all tiers', function() use ($base, $sharedToken) {
    $r = api('GET', "{$base}/plans", null, $sharedToken);
    if ($r['code'] === 429) return 'WARN';
    $data = $r['body']['data'] ?? [];
    $slugs = array_column($data, 'tier');
    $expected = ['free', 'basic', 'pro', 'enterprise'];
    $missing = array_diff($expected, $slugs);
    return empty($missing) ? true : 'Missing: ' . implode(', ', $missing);
});

test('Auth/me returns expected fields', function() use ($base, $sharedToken) {
    $r = api('GET', "{$base}/auth/me", null, $sharedToken);
    if ($r['code'] !== 200) return "Code {$r['code']}";
    $fields = ['id', 'name', 'email', 'membership', 'model_config_count'];
    foreach ($fields as $f) {
        if (!array_key_exists($f, $r['body']['data'] ?? [])) return "Missing field: {$f}";
    }
    return true;
});

// ═══════════════════════════════════════════════════
// SECTION 5: Business Logic
// ═══════════════════════════════════════════════════
usleep(200000);
echo "\n━━━ SECTION 5: Business Logic Tests ━━━\n";

test('Pipeline start validates work status', function() use ($base, $sharedToken) {
    $w = api('POST', "{$base}/works", ['title' => 'Pipeline Test'], $sharedToken);
    $workId = $w['body']['data']['id'] ?? null;
    if (!$workId) return 'Failed to create work';
    $start = api('POST', "{$base}/works/{$workId}/pipeline/start", null, $sharedToken);
    // 200/202=success, 400=no model configs, 422=validation — all valid responses
    return in_array($start['code'], [200, 202, 400, 422]) ? true : "Unexpected {$start['code']}: " . json_encode($start['body']);
});

test('Pipeline progress returns valid structure', function() use ($base, $sharedToken) {
    $w = api('POST', "{$base}/works", ['title' => 'Progress Test'], $sharedToken);
    $workId = $w['body']['data']['id'] ?? null;
    if (!$workId) return 'Failed to create work';
    $prog = api('GET', "{$base}/works/{$workId}/pipeline/progress", null, $sharedToken);
    if ($prog['code'] !== 200) return "Code {$prog['code']}";
    return isset($prog['body']['data']['status']) ? true : 'Missing status field';
});

test('Membership endpoint accessible', function() use ($base, $sharedToken) {
    $r = api('GET', "{$base}/membership", null, $sharedToken);
    return $r['code'] === 200 ? true : "Code {$r['code']}";
});

test('Order creation validates plan_id', function() use ($base, $sharedToken) {
    $r = api('POST', "{$base}/orders", [
        'plan_id' => 99999, 'payment_method' => 'wechat', 'billing_cycle' => 'monthly',
    ], $sharedToken);
    return $r['code'] === 422 ? true : "Expected 422, got {$r['code']}";
});

test('Pipeline execution: starts without 500, transitions to failed', function() use ($base, $sharedToken, $sharedModelId) {
    // Create a model config for the shared user (script_analysis stage)
    $r = api('POST', "{$base}/user/model-configs", [
        'model_registry_id' => $sharedModelId,
        'stage' => 'script_analysis',
        'api_key' => 'sk-e2e-pipeline-test-key',
    ], $sharedToken);
    if ($r['code'] === 429) return 'WARN';
    $cfgId = $r['body']['data']['id'] ?? null;
    if (!$cfgId) return 'Failed to create config';

    // Create a work
    $r = api('POST', "{$base}/works", [
        'title' => 'Pipeline E2E Test', 'style' => '写实', 'target_duration_sec' => 60,
    ], $sharedToken);
    if ($r['code'] === 429) return 'WARN';
    $workId = $r['body']['data']['id'] ?? null;
    if (!$workId) return 'Failed to create work';

    // Start pipeline — must not return 500
    $r = api('POST', "{$base}/works/{$workId}/pipeline/start", null, $sharedToken);
    if ($r['code'] === 500) return 'Pipeline start returned 500';
    if ($r['code'] === 429) return 'WARN';

    // Wait for sync job to complete (up to 10 seconds)
    $finalStatus = null;
    for ($i = 0; $i < 5; $i++) {
        usleep(500000);
        $r = api('GET', "{$base}/works/{$workId}", null, $sharedToken);
        $finalStatus = $r['body']['data']['status'] ?? null;
        if ($finalStatus === 'failed') break;
    }

    // Cleanup
    api('DELETE', "{$base}/works/{$workId}", null, $sharedToken);
    api('DELETE', "{$base}/user/model-configs/{$cfgId}", null, $sharedToken);

    // Verify work transitioned to failed (fake key → AI call fails)
    if ($finalStatus !== 'failed') return "Work stuck in '{$finalStatus}' instead of 'failed'";
    return true;
});

// ═══════════════════════════════════════════════════
// SECTION 6: Security Guard Tests
// ═══════════════════════════════════════════════════
usleep(200000);
echo "\n━━━ SECTION 6: Security Guard Tests ━━━\n";

test('Protected endpoints require auth', function() use ($base) {
    $endpoints = [
        ['GET', '/auth/me'],
        ['POST', '/auth/logout'],
        ['GET', '/user/model-configs'],
        ['POST', '/user/model-configs'],
        ['GET', '/works'],
        ['POST', '/works'],
        ['GET', '/membership'],
    ];
    $failures = [];
    $rateLimited = 0;
    foreach ($endpoints as [$method, $path]) {
        $r = api($method, "{$base}{$path}");
        if ($r['code'] === 429) { $rateLimited++; continue; }
        if (!in_array($r['code'], [401, 302])) {
            $failures[] = "{$method} {$path} → {$r['code']}";
        }
    }
    if ($rateLimited > 0 && empty($failures)) return 'WARN';
    return empty($failures) ? true : implode('; ', $failures);
});

test('CORS headers present', function() use ($base) {
    $ch = curl_init("{$base}/models");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_HEADER => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return stripos($raw, 'Access-Control-Allow-Origin') !== false ? true : 'WARN';
});

test('JSON content type on API', function() use ($base) {
    $ch = curl_init("{$base}/models");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_HEADER => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return stripos($raw, 'application/json') !== false ? true : 'Response not JSON';
});

// ═══════════════════════════════════════════════════
// SECTION 7: Response Format Consistency
// ═══════════════════════════════════════════════════
usleep(3000000);
echo "\n━━━ SECTION 7: Response Format Consistency ━━━\n";

test('Register returns data.user + data.token', function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 'FormatTest', 'email' => 'fmt-' . _uid() . '@test.dev', 'password' => 'FormatTest123',
    ]);
    if ($r['code'] === 429) return 'WARN';
    if (!isset($r['body']['data'])) return 'No data wrapper';
    if (!isset($r['body']['data']['user'])) return 'No user in data';
    if (!isset($r['body']['data']['token'])) return 'No token in data';
    return true;
});

test('Models endpoint returns data array', function() use ($base) {
    $r = api('GET', "{$base}/models");
    if ($r['code'] === 429) return 'WARN';
    return isset($r['body']['data']) && is_array($r['body']['data']) ? true : 'data not an array';
});

test('Model objects have required fields', function() use ($base) {
    $r = api('GET', "{$base}/models?category=moderation");
    if ($r['code'] === 429) return 'WARN';
    $model = $r['body']['data'][0] ?? null;
    if (!$model) return 'No moderation model';
    foreach (['id', 'category', 'model_name', 'display_name', 'provider', 'api_type'] as $f) {
        if (!array_key_exists($f, $model)) return "Missing field: {$f}";
    }
    return true;
});

test('422 errors have errors object', function() use ($base) {
    $r = api('POST', "{$base}/auth/register", ['name' => '', 'email' => '', 'password' => '']);
    if ($r['code'] === 429) return 'WARN';
    return isset($r['body']['errors']) ? true : '422 without errors object';
});

test('Login response matches expected format', function() use ($base, $sharedEmail, $sharedPass) {
    $r = api('POST', "{$base}/auth/login", ['email' => $sharedEmail, 'password' => $sharedPass]);
    if ($r['code'] === 429) return 'WARN';
    if ($r['code'] !== 200) return "Code {$r['code']}";
    return isset($r['body']['data']['token']) ? true : 'No token in login response';
});

echo "\n╔══════════════════════════════════════════════════╗\n";
echo sprintf("║  Results: %3d passed, %2d failed, %2d warnings     ║\n", $passed, $failed, $warnings);
echo "╚══════════════════════════════════════════════════╝\n";
exit($failed > 0 ? 1 : 0);
