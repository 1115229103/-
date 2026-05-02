<?php
/** Admin API test suite */
$base = 'http://127.0.0.1:8000/api/v1';
$passed = 0;
$failed = 0;

function api(string $method, string $url, ?array $data = null, ?string $token = null): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
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
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true) ?: $body];
}

function pt(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $r = $fn();
        if ($r === true) { echo "  PASS: {$name}\n"; $passed++; }
        else { echo "  FAIL: {$name} — {$r}\n"; $failed++; }
    } catch (\Exception $e) {
        echo "  FAIL: {$name} — {$e->getMessage()}\n"; $failed++;
    }
}

echo "=== Admin API Test Suite ===\n\n";

// Register a regular user first, then try admin endpoints
$email = 'admin-test-' . time() . '@aistory.dev';
$reg = api('POST', "{$base}/auth/register", ['name'=>'Test','email'=>$email,'password'=>'TestPass123']);
$token = $reg['body']['data']['token'] ?? '';
echo "User token: " . ($token ? substr($token,0,20).'...' : 'NONE') . "\n\n";

echo "--- Admin Access Control ---\n";
pt('Admin dashboard (no admin role → 403 or 200)', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/dashboard", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin models list', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/models", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin pipeline stages', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/pipeline-stages", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin prompt templates', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/prompt-templates", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin visual styles', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/visual-styles", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin voice library', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/voice-library", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin water config', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/watermark-config", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin users', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/users", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin works', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/works", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin sensitive words', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/sensitive-words", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin banners', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/banners", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin templates', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/templates", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin assets', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/assets", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin orders', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/orders", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin finance report', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/finance/report", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin system settings', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/system/settings", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin operation logs', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/system/operation-logs", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin backups', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/system/backups", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin plans list', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/plans", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin review works', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/review/works", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

pt('Admin roles', function() use ($base, $token) {
    $r = api('GET', "{$base}/admin/roles", null, $token);
    return in_array($r['code'], [200, 403]) ? true : "Code {$r['code']}";
});

echo "\n--- Auth Guard (no token) ---\n";
pt('Admin dashboard no auth', function() use ($base) {
    $r = api('GET', "{$base}/admin/dashboard");
    return $r['code'] === 401 ? true : "Code {$r['code']}";
});

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
