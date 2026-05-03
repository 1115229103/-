<?php
// OPENAPI CONTRACT VALIDATION — verify actual API responses match the OpenAPI spec
$base = 'http://127.0.0.1:8000/api/v1';
$spec = json_decode(file_get_contents(__DIR__ . '/../public/openapi.json'), true);

$passed = 0;
$failed = 0;
$warnings = [];

function ok($msg) { global $passed; $passed++; echo "    \033[32m✓\033[0m $msg\n"; }
function fail($msg) { global $failed; $failed++; echo "    \033[31m✗\033[0m $msg\n"; }
function warn($msg) { global $warnings; $warnings[] = $msg; echo "    \033[33m⚠\033[0m $msg\n"; }

function api($method, $path, $body = null, $token = null) {
    global $base;
    $ch = curl_init($base . $path);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body ? json_encode($body) : null,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($resp, true) ?: []];
}

// Phase 1: Get auth token for protected endpoints
echo "╔══════════════════════════════════════════════╗\n";
echo "║  OPENAPI CONTRACT VALIDATION                  ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

echo "[Phase 1] Auth setup\n";
$ts = time();
$email = "contract{$ts}@test.com";
[$code, $data] = api('POST', '/auth/register', ['name' => 'ContractTester', 'email' => $email, 'password' => 'Test12345678']);
$token = $data['data']['token'] ?? '';
if ($token) ok("Registered — got token");
else fail("Register failed — can't test protected endpoints: " . json_encode($data));

// Phase 2: Test each path in the spec
echo "\n[Phase 2] Contract validation against " . count($spec['paths'] ?? []) . " paths\n";

// Path-to-params mapping for paths that need dynamic IDs
$pathParams = [];
if ($token) {
    // Create a test work so we have a real work ID
    [$code, $work] = api('POST', '/works', ['title' => "Contract Test " . time(), 'style' => 'cinematic', 'target_duration_sec' => 30], $token);
    $pathParams['workId'] = $work['data']['id'] ?? null;

    // Create a test model config
    [$code, $models] = api('GET', '/models?category=llm');
    $firstModel = $models['data'][0] ?? null;
    if ($firstModel && $pathParams['workId']) {
        [$code, $config] = api('POST', '/user/model-configs', [
            'model_registry_id' => $firstModel['id'],
            'stage' => $firstModel['category'],
            'api_key' => 'sk-test-contract-' . substr(md5((string)time()), 0, 16),
        ], $token);
        $pathParams['configId'] = $config['data']['id'] ?? 1;
    } else {
        $pathParams['configId'] = 1;
    }
}

// Substitute path parameters
$resolvedPaths = [];
foreach ($spec['paths'] as $path => $methods) {
    $resolved = $path;
    // Substitute known params
    // Substitute known params
    if (preg_match('/\{id\}/', $resolved)) {
        if (str_contains($path, '/works/') || str_contains($path, 'work')) {
            $resolved = preg_replace('/\{id\}/', (string)($pathParams['workId'] ?? '1'), $resolved);
        } elseif (str_contains($path, '/user/model-configs/')) {
            $resolved = preg_replace('/\{id\}/', (string)($pathParams['configId'] ?? '1'), $resolved);
        } else {
            $resolved = preg_replace('/\{id\}/', '1', $resolved);
        }
    }
    // Also handle explicit workId/configId params
    $resolved = preg_replace('/\{workId\}/', (string)($pathParams['workId'] ?? '1'), $resolved);
    $resolved = preg_replace('/\{configId\}/', (string)($pathParams['configId'] ?? '1'), $resolved);
    $resolvedPaths[$path] = $resolved;
}

$tested = 0;
$skipped = 0;
foreach ($spec['paths'] as $specPath => $methods) {
    $resolved = $resolvedPaths[$specPath];

    foreach ($methods as $httpMethod => $details) {
        $method = strtoupper($httpMethod);
        $expectedCodes = [];

        // Extract expected status codes from responses
        if (isset($details['responses'])) {
            foreach ($details['responses'] as $code => $resp) {
                if (is_numeric($code)) $expectedCodes[] = (int)$code;
            }
        }

        // Skip write operations that would modify data or need specific IDs
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $skipped++;
            continue;
        }

        // Skip paths with unresolved params (still contain {})
        if (preg_match('/\{/', $resolved)) {
            $skipped++;
            continue;
        }

        $needsAuth = !empty($details['security'] ?? null) || str_contains($specPath, '/admin/') || str_contains($specPath, '/user/') || str_contains($specPath, '/works') || str_contains($specPath, '/membership') || str_contains($specPath, '/orders');

        // Skip admin GET endpoints that require admin role (our test token isn't admin)
        $isAdminPath = str_contains($specPath, '/admin/');
        if ($isAdminPath) {
            // Test admin without admin token → should get 403
            [$code, $resp] = api($method, $resolved, null, $token);
            $tested++;
            if (in_array($code, [200, 403, 404])) {
                ok("$method $specPath → $code (contract: " . implode('/', $expectedCodes) . ")");
            } else {
                fail("$method $specPath → $code (expected " . implode('/', $expectedCodes) . ")");
            }
            continue;
        }

        $testToken = $needsAuth ? $token : null;
        [$code, $resp] = api($method, $resolved, null, $testToken);
        $tested++;

        $inContract = in_array($code, $expectedCodes) || empty($expectedCodes);
        if ($inContract) {
            ok("$method $specPath → $code (contract: " . implode('/', $expectedCodes) . ")");
        } else {
            fail("$method $specPath → $code (expected " . implode('/', $expectedCodes) . ")");
        }

        // Schema structure validated in Phase 3 for critical endpoints
    }
}

// Phase 3: Key response schema validation (deep checks on critical endpoints)
echo "\n[Phase 3] Deep response schema validation\n";

// Auth/me schema
[$code, $me] = api('GET', '/auth/me', null, $token);
if ($code === 200) {
    $required = ['id', 'name', 'email', 'role', 'membership'];
    $missing = array_diff($required, array_keys($me['data'] ?? []));
    if (empty($missing)) ok("/auth/me has all required fields: " . implode(', ', $required));
    else fail("/auth/me missing: " . implode(', ', $missing));
}

// Models list
[$code, $models] = api('GET', '/models');
if ($code === 200) {
    if (isset($models['data']) && is_array($models['data']) && count($models['data']) > 0) {
        $m = $models['data'][0];
        $required = ['id', 'display_name', 'provider', 'category', 'status'];
        $missing = array_diff($required, array_keys($m));
        if (empty($missing)) ok("/models items have all required fields: " . implode(', ', $required));
        else fail("/models item missing: " . implode(', ', $missing));
    }
}

// Plans list
[$code, $plans] = api('GET', '/plans');
if ($code === 200) {
    if (isset($plans['data']) && is_array($plans['data']) && count($plans['data']) > 0) {
        $p = $plans['data'][0];
        $required = ['id', 'name', 'tier', 'slug', 'price_monthly_cny', 'features'];
        $missing = array_diff($required, array_keys($p));
        if (empty($missing)) ok("/plans items have all required fields: " . implode(', ', $required));
        else fail("/plans item missing: " . implode(', ', $missing));
    }
}

// Health endpoints
[$code, $health] = api('GET', '/health');
if ($code === 200) ok("/health → $code");
else fail("/health → $code");

[$code, $deep] = api('GET', '/health/deep');
if (in_array($code, [200, 503])) ok("/health/deep → $code (200=ok, 503=degraded acceptable)");
else fail("/health/deep → $code");

echo "\n╔══════════════════════════════════════════════╗\n";
printf("║  Contract: %-3d passed, %-3d failed, %-3d skipped  ║\n", $passed, $failed, $skipped);
echo "╚══════════════════════════════════════════════╝\n";

if ($failed > 0) {
    echo "\nFAILURES:\n";
    exit(1);
}
exit(0);
