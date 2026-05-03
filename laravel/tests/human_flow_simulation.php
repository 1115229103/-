<?php
// HUMAN FLOW SIMULATION — complete user journey with validation
$base = 'http://127.0.0.1:8000/api/v1';
$errors = [];
$warnings = [];

function step($n, $desc) { echo "\n  [" . $n . "/14] " . $desc . PHP_EOL; }
function ok($msg) { echo "    \033[32m✓\033[0m " . $msg . PHP_EOL; }
function warn($msg) { global $warnings; $warnings[] = $msg; echo "    \033[33m⚠\033[0m " . $msg . PHP_EOL; }
function err($msg) { global $errors; $errors[] = $msg; echo "    \033[31m✗\033[0m " . $msg . PHP_EOL; }

function api($method, $path, $body = null, $token = null) {
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_filter([
            'Content-Type: application/json',
            'Accept: application/json',
            $token ? "Authorization: Bearer $token" : null,
        ]),
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($resp, true) ?: []];
}

$ts = time();
$email = "sim{$ts}@test.com";
$password = 'HumanTest123';
$name = 'HumanTester';

echo "╔══════════════════════════════════════════════╗\n";
echo "║  HUMAN FLOW SIMULATION — " . date('H:i:s') . "                  ║\n";
echo "╚══════════════════════════════════════════════╝\n";

// PHASE 1: Register
step(1, 'Register new account');
[$code, $data] = api('POST', '/auth/register', ['name' => $name, 'email' => $email, 'password' => $password]);
if ($code === 201 && ($data['data']['user']['email'] ?? '') === $email) {
    ok('Registered — email: ' . $data['data']['user']['email'] . ', id: ' . $data['data']['user']['id']);
    $token = $data['data']['token'];
} else {
    err("Registration failed: HTTP $code — " . json_encode($data));
    exit(1);
}

// PHASE 2: Login
step(2, 'Login with new account');
[$code, $data] = api('POST', '/auth/login', ['email' => $email, 'password' => $password]);
$token = $data['data']['token'] ?? '';
if ($token) ok('Login OK — got fresh token'); else err("Login failed: HTTP $code");

// PHASE 3: Membership
step(3, 'View membership (auto-assigned free plan)');
[$code, $data] = api('GET', '/membership', null, $token);
$tier = $data['data']['plan']['tier'] ?? null;
if ($tier === 'free') ok('Free plan auto-assigned'); else warn('Membership tier: ' . ($tier ?? 'null'));

// PHASE 4: Browse models
step(4, 'Browse public AI model catalog');
[$code, $data] = api('GET', '/models');
$modelCount = count($data['data'] ?? []);
$cats = ['llm','image_gen','video_gen','tts','image_understanding','video_understanding'];
$found = 0;
if (is_array($data['data'] ?? null)) {
    foreach ($data['data'] as $m) if (in_array($m['category'] ?? '', $cats)) $found++;
}
ok("{$modelCount} models, {$found}/6 categories covered");

// PHASE 5: Categories
step(5, 'Browse models by category');
[$code, $data] = api('GET', '/models/categories');
$catCount = count($data['data'] ?? []);
ok("{$catCount} categories with staged models");

// PHASE 6: Add API key (need model_registry_id + stage)
step(6, 'Add OpenAI API key for LLM models');
// First find a model to configure (mimics UI flow: browse models → pick one → configure key)
[,$modelsData] = api('GET', '/models?category=llm');
$firstModel = $modelsData['data'][0] ?? null;
if ($firstModel) {
    [$code, $data] = api('POST', '/user/model-configs', [
        'model_registry_id' => $firstModel['id'],
        'stage' => $firstModel['category'], // UI uses category as stage (backend handles mapping)
        'api_key' => 'sk-test-sim-' . substr(md5((string)$ts), 0, 16),
    ], $token);
    $configId = $data['data']['id'] ?? null;
    if ($configId) ok('API key saved — id: ' . $configId . ' for model: ' . $firstModel['display_name']);
    else err('Key save failed — ' . json_encode($data));
} else {
    err('No LLM models available to configure');
    $configId = null;
}

// PHASE 7: List configs
step(7, 'List my API key configs');
[$code, $data] = api('GET', '/user/model-configs', null, $token);
$cfgCount = count($data['data'] ?? []);
if ($cfgCount >= 1) ok("{$cfgCount} config(s) listed"); else warn('No configs found');

// PHASE 8: Create work
step(8, 'Create a new video work');
[$code, $data] = api('POST', '/works', ['title' => "Human Simulation $ts", 'style' => 'cinematic', 'target_duration_sec' => 30], $token);
$workId = $data['data']['id'] ?? null;
if ($workId) ok('Work created — id: ' . $workId . ', status: ' . ($data['data']['status'] ?? '?'));
else err('Work creation failed — ' . json_encode($data));

// PHASE 9: List works
if ($workId) {
step(9, 'List my works');
[$code, $data] = api('GET', '/works', null, $token);
$workCount = count($data['data'] ?? []);
if ($workCount >= 1) ok("{$workCount} work(s) in list"); else warn('No works in list');

// PHASE 10: Work detail
step(10, 'View work detail with sub-resources');
[$code, $data] = api('GET', "/works/{$workId}", null, $token);
$subCount = 0;
foreach (['script','characters','scenes','storyboards','audio_tracks','subtitles'] as $k) {
    if (isset($data['data'][$k])) $subCount++;
}
ok("{$subCount}/6 sub-resources present");

// PHASE 11: Pipeline progress
step(11, 'Check pipeline progress');
[$code, $data] = api('GET', "/works/{$workId}/pipeline/progress", null, $token);
$state = $data['data']['state'] ?? 'unknown';
ok("Pipeline state: {$state}");
}

// PHASE 12: Admin login
$adminEmail = 'admin@aistory.dev';
step(12, 'Login as admin');
[$code, $data] = api('POST', '/auth/login', ['email' => $adminEmail, 'password' => 'Admin123456']);
$adminToken = $data['data']['token'] ?? '';
if ($adminToken) ok('Admin login OK'); else warn("Admin login failed (HTTP $code) — admin user may not exist");

if ($adminToken) {
    // PHASE 13: Admin endpoints
    step(13, 'Admin: browse management endpoints');
    $endpoints = ['plans','models','roles','prompt-templates','system/settings','action-templates','watermark-config','banners'];
    $accessible = 0;
    foreach ($endpoints as $ep) {
        [$code] = api('GET', "/admin/{$ep}", null, $adminToken);
        if ($code >= 200 && $code < 300) $accessible++;
    }
    ok("{$accessible}/8 admin endpoints accessible");
} else {
    step(13, 'Admin: skipped (no admin user)');
}

// PHASE 14: Logout
step(14, 'Logout and verify token invalidation');
[$code] = api('POST', '/auth/logout', null, $token);
if ($code === 204) {
    [$code2] = api('GET', '/auth/me', null, $token);
    if ($code2 === 401) ok('Logout OK — token invalidated');
    else warn("Token still valid after logout (HTTP $code2)");
} else {
    warn("Logout returned HTTP $code");
}

// RESULTS
echo "\n╔══════════════════════════════════════════════╗\n";
echo "║  SIMULATION COMPLETE                          ║\n";
echo "╠══════════════════════════════════════════════╣\n";
printf("║  Errors:   %-3d  Warnings: %-3d                ║\n", count($errors), count($warnings));
echo "╚══════════════════════════════════════════════╝\n";

if ($errors) { echo "\nERRORS:\n"; foreach ($errors as $e) echo "  ✗ $e\n"; }
if ($warnings) { echo "\nWARNINGS:\n"; foreach ($warnings as $w) echo "  ⚠ $w\n"; }
exit(count($errors) > 0 ? 1 : 0);
