<?php
/**
 * API Response Time Benchmark
 * Measures every endpoint's latency. Flags slow endpoints (>500ms).
 * Fundamentally different from all prior testing — correctness vs. performance.
 */
$base = 'http://127.0.0.1:8000/api/v1';
$slowThreshold = 500; // ms — flag anything over this
$warmupRounds = 2;
$measureRounds = 3;
$results = [];
$slow = [];

function bench(string $method, string $url, ?array $data = null, ?string $token = null): array {
    $times = [];
    $code = 0;
    for ($i = 0; $i < $GLOBALS['warmupRounds'] + $GLOBALS['measureRounds']; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => array_filter([
                'Accept: application/json',
                'Content-Type: application/json',
                $token ? "Authorization: Bearer {$token}" : null,
            ]),
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
        $ttfb = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) * 1000; // ms
        curl_close($ch);
        if ($i >= $GLOBALS['warmupRounds']) $times[] = $ttfb;
    }
    $avg = count($times) > 0 ? array_sum($times) / count($times) : 0;
    $min = count($times) > 0 ? min($times) : 0;
    $max = count($times) > 0 ? max($times) : 0;
    return ['code' => $code, 'avg' => round($avg, 1), 'min' => round($min, 1), 'max' => round($max, 1)];
}

function add(string $name, string $method, string $path, ?array $data = null, ?string $token = null): void {
    global $base, $results, $slow, $slowThreshold;
    $r = bench($method, $base . $path, $data, $token);
    $results[] = ['name' => $name, 'path' => $path, 'method' => $method, 'code' => $r['code'], 'avg' => $r['avg'], 'min' => $r['min'], 'max' => $r['max']];
    if ($r['avg'] > $slowThreshold) $slow[] = $name . ': ' . $r['avg'] . 'ms avg';
}

echo str_repeat('=', 70) . "\n";
echo "  AIStory API Response Time Benchmark\n";
echo "  Threshold: {$slowThreshold}ms | Warmup: {$warmupRounds} | Measure: {$measureRounds}\n";
echo str_repeat('=', 70) . "\n\n";

// ── Setup: get a user token ──
echo "Setting up test user...\n";
$email = 'bench-' . time() . '@aistory.dev';
$ch = curl_init($base . '/auth/register');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['name' => 'Bench', 'email' => $email, 'password' => 'Test123456']),
]);
$body = curl_exec($ch);
$data = json_decode($body, true);
curl_close($ch);
$token = $data['data']['token'] ?? '';
$userId = $data['data']['user']['id'] ?? 1;

if (!$token) { echo "FATAL: Could not register test user\n"; exit(1); }

// Get admin token
$ch = curl_init($base . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['email' => 'admin@aistory.dev', 'password' => 'Admin123456']),
]);
$adminBody = curl_exec($ch);
$adminData = json_decode($adminBody, true);
curl_close($ch);
$adminToken = $adminData['data']['token'] ?? '';

// Create a work for detail endpoints
$ch = curl_init($base . '/works');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', "Authorization: Bearer {$token}"],
    CURLOPT_POSTFIELDS => json_encode(['title' => 'Bench Work', 'style' => 'cinematic', 'target_duration_sec' => 60]),
]);
$wBody = curl_exec($ch);
$workId = json_decode($wBody, true)['data']['id'] ?? 1;
curl_close($ch);

echo "User token acquired. Work #{$workId} created.\n\n";

// ═══════════════════════════════════════════════════
// PUBLIC ENDPOINTS (no auth)
// ═══════════════════════════════════════════════════
echo "━━━ Public Endpoints (no auth) ━━━\n";

add('Health', 'GET', '/health');
add('Health Deep', 'GET', '/health/deep');
add('Models List', 'GET', '/models');
add('Models Categories', 'GET', '/models/categories');
add('Plans List', 'GET', '/plans');

echo "\n━━━ Auth Endpoints ━━━\n";

add('Register', 'POST', '/auth/register',
    ['name' => 'B2', 'email' => 'b2-' . uniqid() . '@test.ai', 'password' => 'Test1234']);
add('Login', 'POST', '/auth/login', ['email' => $email, 'password' => 'Test123456']);
add('Auth Me', 'GET', '/auth/me', null, $token);
add('Logout', 'POST', '/auth/logout', null, $token);

// Re-login after logout
$ch = curl_init($base . '/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => 'Test123456']),
]);
$token = json_decode(curl_exec($ch), true)['data']['token'] ?? '';
curl_close($ch);

echo "\n━━━ User Works CRUD ━━━\n";

add('Create Work', 'POST', '/works', ['title' => 'Bench2', 'style' => 'anime', 'target_duration_sec' => 120], $token);
add('List Works', 'GET', '/works', null, $token);
add('Show Work', 'GET', "/works/{$workId}", null, $token);
add('Update Work', 'PUT', "/works/{$workId}", ['title' => 'Updated'], $token);
add('Pipeline Progress', 'GET', "/works/{$workId}/pipeline/progress", null, $token);

echo "\n━━━ Model Config Management ━━━\n";

$modelsResp = json_decode(file_get_contents($base . '/models'), true);
$firstModel = $modelsResp['data'][0] ?? ['id' => 1, 'category' => 'llm'];

add('Add Model Config', 'POST', '/user/model-configs', [
    'model_registry_id' => $firstModel['id'],
    'stage' => $firstModel['category'],
    'api_key' => 'sk-bench-' . uniqid(),
    'priority' => 1,
], $token);
add('List Configs', 'GET', '/user/model-configs', null, $token);

echo "\n━━━ Membership & Billing ━━━\n";

add('Get Membership', 'GET', '/membership', null, $token);
add('Create Order (invalid)', 'POST', '/orders', ['plan_id' => 99999], $token);

echo "\n━━━ Admin Endpoints ━━━\n";

if ($adminToken) {
    $adminQuick = ['dashboard', 'users', 'works', 'models', 'pipeline-stages', 'plans',
        'prompt-templates', 'visual-styles', 'voice-library', 'watermark-config',
        'sensitive-words', 'banners', 'action-templates', 'templates', 'assets',
        'orders', 'roles', 'review/works', 'finance/report', 'system/settings'];
    foreach ($adminQuick as $ep) {
        add("admin/{$ep}", 'GET', "/admin/{$ep}", null, $adminToken);
    }
} else {
    echo "  (admin login failed — skipping admin benchmarks)\n";
}

echo "\n━━━ Security Endpoints ━━━\n";

add('Invalid Token → 401', 'GET', '/auth/me', null, 'bad_token');
add('No Auth → 401', 'GET', '/works', null, null);

// ═══════════════════════════════════════════════════
// Summary
// ═══════════════════════════════════════════════════
echo "\n" . str_repeat('=', 70) . "\n";
echo "  Benchmark Results\n";
echo str_repeat('=', 70) . "\n\n";

// Sort by avg time descending
usort($results, fn($a, $b) => $b['avg'] <=> $a['avg']);

printf("  %-35s %6s %8s %8s %8s %5s\n", 'Endpoint', 'Method', 'Avg(ms)', 'Min(ms)', 'Max(ms)', 'Code');
echo "  " . str_repeat('-', 70) . "\n";

$totalAvg = 0;
foreach ($results as $r) {
    $flag = $r['avg'] > $slowThreshold ? ' ⚠ SLOW' : '';
    printf("  %-35s %6s %8.1f %8.1f %8.1f %5d%s\n",
        $r['name'], $r['method'], $r['avg'], $r['min'], $r['max'], $r['code'], $flag);
    $totalAvg += $r['avg'];
}
$overallAvg = count($results) > 0 ? $totalAvg / count($results) : 0;

echo "\n  ───────────────────────────────────\n";
printf("  Endpoints tested: %d\n", count($results));
printf("  Overall avg TTFB: %.1f ms\n", $overallAvg);
printf("  Slow endpoints (>%dms): %d\n", $slowThreshold, count($slow));
printf("  Fastest: %s (%.1f ms)\n", $results[count($results)-1]['name'] ?? 'N/A', $results[count($results)-1]['avg'] ?? 0);
printf("  Slowest: %s (%.1f ms)\n", $results[0]['name'] ?? 'N/A', $results[0]['avg'] ?? 0);

if ($slow) {
    echo "\n  ⚠ SLOW ENDPOINTS (>500ms):\n";
    foreach ($slow as $s) echo "    • {$s}\n";
} else {
    echo "\n  ✅ No endpoints exceed {$slowThreshold}ms threshold\n";
}

echo "\n";