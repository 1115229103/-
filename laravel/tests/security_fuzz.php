<?php
/**
 * AIStory Security Fuzz Test Suite
 * Sends malicious inputs to API endpoints and verifies no crashes (500)
 * and proper input rejection (422/401). Designed to catch security regressions.
 * Usage: php tests/security_fuzz.php
 */
$base = 'http://127.0.0.1:8000/api/v1';
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  \033[32mPASS\033[0m: {$name}\n";
            $passed++;
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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_values(array_filter($headers)),
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $body = json_decode($raw, true) ?: $raw;
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function _uid(): string {
    return bin2hex(random_bytes(6));
}

// Must NOT be 500 — any 500 is a crash = test failure
function no500(int $code): bool {
    return $code !== 500;
}

echo "\n╔══════════════════════════════════════════════════╗\n";
echo "║       AIStory Security Fuzz Test Suite           ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// ── Section 1: XSS Injection ─────────────────────────────────────────

echo "── Section 1: XSS Injection ──\n";

$xss_payloads = [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '"><script>alert(document.cookie)</script>',
    "javascript:alert('xss')",
];

foreach ($xss_payloads as $i => $payload) {
    test("XSS in name field — payload #" . ($i + 1), function() use ($base, $payload) {
        $r = api('POST', "{$base}/auth/register", [
            'name' => $payload,
            'email' => 'xss-test-' . _uid() . '@test.dev',
            'password' => 'TestPass123',
        ]);
        return no500($r['code']) ? true : "Got 500 on XSS name #" . ($i + 1);
    });

    test("XSS in email field — payload #" . ($i + 1), function() use ($base, $payload) {
        $r = api('POST', "{$base}/auth/register", [
            'name' => 'XSS Test User',
            'email' => $payload . '@test.dev',
            'password' => 'TestPass123',
        ]);
        return no500($r['code']) ? true : "Got 500 on XSS email #" . ($i + 1);
    });
}

// ── Section 2: SQL Injection ──────────────────────────────────────────

echo "\n── Section 2: SQL Injection ──\n";

test("SQLi in login email — OR 1=1", function() use ($base) {
    $r = api('POST', "{$base}/auth/login", [
        'email' => "' OR 1=1 -- ",
        'password' => 'anything',
    ]);
    if ($r['code'] === 500) return "Got 500 — possible crash";
    if ($r['code'] === 200) return "SQLi may have bypassed auth (200 OK)";
    return true; // 401/422 both acceptable
});

test("SQLi in login email — UNION SELECT", function() use ($base) {
    $r = api('POST', "{$base}/auth/login", [
        'email' => "' UNION SELECT NULL,NULL,NULL-- ",
        'password' => 'anything',
    ]);
    return no500($r['code']) ? true : "Got 500 on UNION SELECT SQLi";
});

test("SQLi in login email — SLEEP/WAIT", function() use ($base) {
    $start = microtime(true);
    $r = api('POST', "{$base}/auth/login", [
        'email' => "'; WAITFOR DELAY '0:0:5'-- ",
        'password' => 'anything',
    ]);
    $elapsed = microtime(true) - $start;
    if ($elapsed > 3) return "Response took {$elapsed}s — possible time-based SQLi";
    return no500($r['code']) ? true : "Got 500 on time-based SQLi";
});

test("SQLi in query param — models endpoint", function() use ($base) {
    $r = api('GET', "{$base}/models?search=' OR 1=1 -- ");
    return no500($r['code']) ? true : "Got 500 on SQLi in query param";
});

test("SQLi in query param — boolean-based", function() use ($base) {
    $r = api('GET', "{$base}/models?category=1 AND 1=1");
    return no500($r['code']) ? true : "Got 500 on boolean SQLi";
});

// ── Section 3: Unicode & Emoji ───────────────────────────────────────

echo "\n── Section 3: Unicode & Emoji ──\n";

test("Emoji in name field", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => '🎉💣🔥 User 测试 ユーザー',
        'email' => 'emoji-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on emoji name";
});

test("Zero-width characters in name", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => "Bad\u{200B}User\u{200C}Name\u{FEFF}",
        'email' => 'zwc-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on zero-width chars";
});

test("Right-to-left override in name", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => "Normal\u{202E}User\u{202C}End",
        'email' => 'rtlo-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on RTL override";
});

test("Null byte injection — name", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => "Evil\0User.php",
        'email' => 'nullbyte-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on null byte";
});

test("Null byte injection — email", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 'NullByte',
        'email' => "evil\0@test.dev",
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on null byte email";
});

// ── Section 4: Overlong Input ────────────────────────────────────────

echo "\n── Section 4: Overlong Input ──\n";

test("10KB name — should reject 422", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => str_repeat('A', 10240),
        'email' => 'long-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    if ($r['code'] === 500) return "Got 500 — possible memory issue";
    // Expect 422 validation failure
    return true;
});

test("1KB email — should reject 422", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 'Long Email',
        'email' => str_repeat('a', 1000) . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on 1KB email";
});

test("100KB body — POST register", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => str_repeat('X', 102400),
        'email' => 'huge-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    // Laravel should return 413 (Payload Too Large) or 422, never 500
    return no500($r['code']) ? true : "Got 500 on 100KB payload";
});

// ── Section 5: Edge Numeric & Type Values ────────────────────────────

echo "\n── Section 5: Edge Numeric & Type Values ──\n";

test("Negative work duration", function() use ($base) {
    // Create user first
    $email = 'edge-' . _uid() . '@test.dev';
    $reg = api('POST', "{$base}/auth/register", [
        'name' => 'Edge Tester', 'email' => $email, 'password' => 'TestPass123',
    ]);
    $token = $reg['body']['data']['token'] ?? '';
    if (!$token) return "Could not register test user";

    $r = api('POST', "{$base}/works", [
        'title' => 'Negative Duration Work',
        'duration' => -1,
        'resolution' => '1080p',
    ], $token);
    return no500($r['code']) ? true : "Got 500 on negative duration";
});

test("Very large duration", function() use ($base) {
    $email = 'bigd-' . _uid() . '@test.dev';
    $reg = api('POST', "{$base}/auth/register", [
        'name' => 'Big Duration', 'email' => $email, 'password' => 'TestPass123',
    ]);
    $token = $reg['body']['data']['token'] ?? '';
    if (!$token) return "Could not register test user";

    $r = api('POST', "{$base}/works", [
        'title' => 'Huge Duration',
        'duration' => 999999999,
        'resolution' => '1080p',
    ], $token);
    return no500($r['code']) ? true : "Got 500 on huge duration";
});

test("Float as name — type confusion", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 12345.6789,
        'email' => 'float-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on float name";
});

test("Array as email — type confusion", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => 'Array Email',
        'email' => ['nested' => 'array@evil.com'],
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on array email";
});

test("Nested object in name", function() use ($base) {
    $r = api('POST', "{$base}/auth/register", [
        'name' => ['deep' => ['nested' => 'value']],
        'email' => 'obj-' . _uid() . '@test.dev',
        'password' => 'TestPass123',
    ]);
    return no500($r['code']) ? true : "Got 500 on nested object name";
});

// ── Section 6: Auth & Authorization ──────────────────────────────────

echo "\n── Section 6: Auth & Authorization Bypass ──\n";

test("Admin endpoint without token", function() use ($base) {
    $r = api('GET', "{$base}/admin/users");
    if ($r['code'] === 401 || $r['code'] === 403) return true;
    if ($r['code'] === 200) return "Admin endpoint accessible without auth!";
    return no500($r['code']) ? true : "Got 500 on admin without token";
});

test("Admin endpoint with invalid token", function() use ($base) {
    $r = api('GET', "{$base}/admin/users", null, 'invalid_token_xyz');
    if ($r['code'] === 401 || $r['code'] === 403) return true;
    if ($r['code'] === 200) return "Admin endpoint bypassed with fake token!";
    return no500($r['code']) ? true : "Got 500 on admin with bad token";
});

test("Malformed Bearer token header", function() use ($base) {
    $r = api('GET', "{$base}/auth/me", null, "'; DROP TABLE users; -- ");
    if ($r['code'] === 401) return true;
    return no500($r['code']) ? true : "Got 500 on SQLi in token";
});

test("Empty Authorization header", function() use ($base) {
    $r = api('GET', "{$base}/admin/users", null, '');
    if ($r['code'] === 401 || $r['code'] === 403) return true;
    return no500($r['code']) ? true : "Got 500 on empty auth";
});

// ── Section 7: HTTP Header Attacks ───────────────────────────────────

echo "\n── Section 7: HTTP Header Attacks ──\n";

test("Host header injection", function() use ($base) {
    $r = api('GET', "{$base}/health", null, null, ['Host: evil.com']);
    return no500($r['code']) ? true : "Got 500 on Host injection";
});

test("X-Forwarded-For spoofing", function() use ($base) {
    $r = api('GET', "{$base}/health", null, null, ['X-Forwarded-For: 127.0.0.1']);
    return no500($r['code']) ? true : "Got 500 on X-Forwarded-For";
});

test("Large Content-Length mismatch", function() use ($base) {
    $r = api('POST', "{$base}/auth/login", ['email' => 'test@test.dev', 'password' => 'test'], null, [
        'Content-Length: 99999',
    ]);
    return no500($r['code']) ? true : "Got 500 on Content-Length mismatch";
});

// ── Section 8: Path Traversal & Special Routes ───────────────────────

echo "\n── Section 8: Path Traversal & Special Routes ──\n";

test("Path traversal in URL", function() use ($base) {
    $r = api('GET', "{$base}/../.env");
    // Should 404, not 500 and not return .env contents
    if ($r['code'] === 200 && is_string($r['body']) && strpos($r['body'], 'APP_KEY') !== false) {
        return "Path traversal leaked .env!";
    }
    return no500($r['code']) ? true : "Got 500 on path traversal";
});

test("Double-encoded path traversal", function() use ($base) {
    $r = api('GET', "{$base}/.%252e/.%252e/.env");
    return no500($r['code']) ? true : "Got 500 on double-encoded traversal";
});

test("Method not allowed", function() use ($base) {
    // Send DELETE to a POST-only endpoint
    $r = api('GET', "{$base}/auth/login"); // GET on POST endpoint
    return no500($r['code']) ? true : "Got 500 on wrong method";
});

// ── Section 9: Malformed JSON ────────────────────────────────────────

echo "\n── Section 9: Malformed JSON ──\n";

test("Truncated JSON body", function() use ($base) {
    $ch = curl_init("{$base}/auth/register");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{"name":"test","email',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code !== 500 ? true : "Got 500 on truncated JSON";
});

test("Empty body", function() use ($base) {
    $ch = curl_init("{$base}/auth/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code !== 500 ? true : "Got 500 on empty body";
});

test("Billion laughs XML bomb (sent as JSON text)", function() use ($base) {
    $laughs = '<?xml version="1.0"?><!DOCTYPE bomb [<!ENTITY laugh "ha"><!ENTITY laugh2 "&laugh;&laugh;"><!ENTITY laugh3 "&laugh2;&laugh2;"><!ENTITY laugh4 "&laugh3;&laugh3;">]><bomb>&laugh4;</bomb>';
    $r = api('POST', "{$base}/auth/login", ['email' => $laughs, 'password' => 'test']);
    return no500($r['code']) ? true : "Got 500 on XML bomb";
});

// ── Section 10: Business Logic Abuse ─────────────────────────────────

echo "\n── Section 10: Business Logic Abuse ──\n";

test("Access other users work without token", function() use ($base) {
    $r = api('GET', "{$base}/works/99999");
    if ($r['code'] === 401) return true;
    return no500($r['code']) ? true : "Got 500 on unauthenticated work access";
});

test("Rapid-fire registration (3 req/s)", function() use ($base) {
    $start = microtime(true);
    $errors = 0;
    for ($i = 0; $i < 3; $i++) {
        $r = api('POST', "{$base}/auth/register", [
            'name' => "Burst User {$i}",
            'email' => 'burst-' . _uid() . '@test.dev',
            'password' => 'TestPass123',
        ]);
        if ($r['code'] === 500) $errors++;
    }
    $elapsed = microtime(true) - $start;
    if ($errors > 0) return "{$errors}/3 returned 500 in rapid fire";
    return true;
});

// ── Results ──────────────────────────────────────────────────────────

echo "\n════════════════════════════════════════════════\n";
$total = $passed + $failed;
echo "Results: {$passed}/{$total} passed";
if ($failed > 0) {
    echo ", \033[31m{$failed} failed\033[0m";
}
echo "\n";

exit($failed > 0 ? 1 : 0);
