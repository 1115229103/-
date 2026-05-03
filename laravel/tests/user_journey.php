<?php
/**
 * AIStory User Journey Simulation
 * Walks through the complete human user flow:
 * Register → Login → Browse → API Key → Create Work → Pipeline → Admin Review
 * Catches response format bugs, missing fields, and UX inconsistencies.
 */
$base = 'http://localhost:8000/api/v1';
$passed = 0;
$failed = 0;
$warnings = [];

function req(string $method, string $path, ?array $body = null, ?string $token = null): array {
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => array_filter([
            'Content-Type: application/json',
            'Accept: application/json',
            $token ? "Authorization: Bearer {$token}" : null,
        ]),
        CURLOPT_POSTFIELDS     => $body ? json_encode($body) : null,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($body, true) ?: ['_raw' => $body]];
}

function pass(string $msg) { global $passed; $passed++; echo "  \033[32mPASS\033[0m: {$msg}\n"; }
function fail(string $msg) { global $failed; $failed++; echo "  \033[31mFAIL\033[0m: {$msg}\n"; }
function warn(string $msg) { global $warnings; $warnings[] = $msg; echo "  \033[33mWARN\033[0m: {$msg}\n"; }

echo str_repeat('=', 60) . "\n";
echo "  AIStory User Journey Simulation\n";
echo str_repeat('=', 60) . "\n\n";

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PHASE 1: Registration & Authentication
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo "━━━ PHASE 1: Registration & Auth ━━━\n";

$email = 'journey_' . uniqid() . '@test.ai';
$password = 'JourneyTest123!';
$name = '旅途测试用户';

// 1.1 Register
[$code, $data] = req('POST', '/auth/register', [
    'name' => $name, 'email' => $email,
    'password' => $password, 'password_confirmation' => $password,
]);
if ($code === 201 && isset($data['data']['token'])) {
    pass('Register returns 201 + token');
    $token = $data['data']['token'];
    // Verify response structure
    if (isset($data['data']['user']) && isset($data['data']['user']['name'])) {
        pass('Register response includes user object');
    } else {
        fail('Register response missing user object');
    }
} else {
    fail("Register failed: HTTP {$code} — " . json_encode($data));
    $token = null;
}

if (!$token) { echo "\nCannot continue — no token.\n"; exit(1); }

// 1.2 Verify /auth/me
[$code, $me] = req('GET', '/auth/me', null, $token);
if ($code === 200 && ($me['data']['email'] ?? '') === $email) {
    pass('/auth/me returns current user with correct email');
} else {
    fail("/auth/me: HTTP {$code}, expected email={$email}");
}

// 1.3 Check /auth/me field completeness
$requiredUserFields = ['id', 'name', 'email', 'role', 'created_at'];
foreach ($requiredUserFields as $f) {
    if (!array_key_exists($f, $me['data'] ?? [])) {
        warn("/auth/me missing field: {$f}");
    }
}

// 1.4 Login (separate session)
[$code, $login] = req('POST', '/auth/login', ['email' => $email, 'password' => $password]);
if ($code === 200 && isset($login['data']['token'])) {
    pass('Login returns 200 + token');
} else {
    fail("Login failed: HTTP {$code}");
}

echo "\n━━━ PHASE 2: Public Data Browsing ━━━\n";

// 2.1 Browse models (no auth)
[$code, $models] = req('GET', '/models');
if ($code === 200 && is_array($models['data'] ?? null)) {
    pass('GET /models returns array (count=' . count($models['data']) . ')');
    // Check model object structure
    if (count($models['data']) > 0) {
        $m = $models['data'][0];
        $modelFields = ['id', 'category', 'model_name', 'display_name', 'provider', 'api_type'];
        foreach ($modelFields as $f) {
            if (!array_key_exists($f, $m)) warn("Model object missing field: {$f}");
        }
    }
} else {
    fail('GET /models failed or data not array');
}

// 2.2 Browse model categories
[$code, $cats] = req('GET', '/models/categories');
if ($code === 200 && is_array($cats['data'] ?? null)) {
    pass('GET /models/categories returns array (count=' . count($cats['data']) . ')');
} else {
    fail('GET /models/categories failed');
}

// 2.3 Browse plans
[$code, $plans] = req('GET', '/plans');
if ($code === 200 && is_array($plans['data'] ?? null)) {
    pass('GET /plans returns array (count=' . count($plans['data']) . ')');
    // Check plan object structure
    if (count($plans['data']) > 0) {
        $p = $plans['data'][0];
        foreach (['id', 'name', 'slug', 'tier', 'price_monthly_cny', 'features'] as $f) {
            if (!array_key_exists($f, $p)) warn("Plan object missing field: {$f}");
        }
        if (is_string($p['features'] ?? null)) warn("Plan features should be array, got string");
    }
} else {
    fail('GET /plans failed');
}

echo "\n━━━ PHASE 3: API Key Management ━━━\n";

// 3.1 Get available LLM models to get a valid model_registry_id
[$code, $llmModels] = req('GET', '/models?category=llm');
$modelId = $llmModels['data'][0]['id'] ?? null;
$modelName = $llmModels['data'][0]['model_name'] ?? 'gpt-4o';
$modelStage = $llmModels['data'][0]['category'] ?? 'llm';
if (!$modelId) warn('No LLM models seeded — model config test will be skipped');

// 3.2 Add model config (API key) — requires model_registry_id, not model_name
if ($modelId) {
    [$code, $config] = req('POST', '/user/model-configs', [
        'model_registry_id' => $modelId,
        'stage' => $modelStage,
        'api_key' => 'sk-test-journey-' . uniqid(),
        'priority' => 1,
    ], $token);
    if ($code === 201) {
        pass("Add model config for {$modelName} → 201");
        $configId = $config['data']['id'] ?? null;
    } else {
        fail("Add model config failed: HTTP {$code} — " . json_encode($config));
        $configId = null;
    }
} else {
    $configId = null;
}

// 3.3 List model configs
[$code, $configs] = req('GET', '/user/model-configs', null, $token);
$configCount = count($configs['data'] ?? []);
if ($code === 200) {
    pass("List model configs: {$configCount} config(s)");
} else {
    fail("List model configs failed: HTTP {$code}");
}

// 3.4 Check config response masks API key
if ($configId) {
    [$code, $single] = req('GET', "/user/model-configs/{$configId}", null, $token);
    $rawKey = $single['data']['api_key'] ?? '';
    if ($rawKey && !str_starts_with($rawKey, 'sk-')) {
        pass('API key appears masked in response (good)');
    } elseif ($rawKey && str_starts_with($rawKey, 'sk-')) {
        warn('API key returned in plaintext — SECURITY RISK');
    }
}

echo "\n━━━ PHASE 4: Work Creation & Pipeline ━━━\n";

// 4.1 Create a work
[$code, $work] = req('POST', '/works', [
    'title' => '用户旅途测试作品 ' . date('His'),
    'style' => 'cinematic',
    'duration' => 60,
    'description' => '这是一个通过自动化测试创建的作品，用于模拟真实用户操作流程。',
], $token);
if ($code === 201 && isset($work['data']['id'])) {
    pass('Create work → 201');
    $workId = $work['data']['id'];
} else {
    fail("Create work failed: HTTP {$code}");
    $workId = null;
}

// 4.2 List works
[$code, $works] = req('GET', '/works', null, $token);
$workCount = count($works['data'] ?? []);
if ($code === 200) {
    pass("List works: {$workCount} work(s)");
} else {
    fail("List works failed: HTTP {$code}");
}

// 4.3 GET single work
if ($workId) {
    [$code, $singleWork] = req('GET', "/works/{$workId}", null, $token);
    if ($code === 200 && ($singleWork['data']['title'] ?? '') !== '') {
        pass('GET single work returns correct title');
        $workStatus = $singleWork['data']['status'] ?? '';
    } else {
        fail("GET single work failed: HTTP {$code}");
    }
}

// 4.4 Update work
if ($workId) {
    [$code, $updated] = req('PUT', "/works/{$workId}", [
        'title' => '更新后的旅途测试作品',
    ], $token);
    if ($code === 200 && ($updated['data']['title'] ?? '') === '更新后的旅途测试作品') {
        pass('Update work title → 200, title changed');
    } else {
        fail("Update work failed: HTTP {$code}");
    }
}

// 4.5 Pipeline progress
if ($workId) {
    [$code, $progress] = req('GET', "/works/{$workId}/pipeline/progress", null, $token);
    if ($code === 200) {
        pass('Pipeline progress returns 200');
    } else {
        fail("Pipeline progress failed: HTTP {$code}");
    }
}

echo "\n━━━ PHASE 5: Membership & Billing ━━━\n";

// 5.1 Check membership
[$code, $membership] = req('GET', '/membership', null, $token);
if ($code === 200) {
    pass('GET /membership → 200');
    // New user should have free tier or null
    $planTier = $membership['data']['plan']['tier'] ?? ($membership['data']['status'] ?? 'none');
    if ($planTier === 'free' || $planTier === null || $planTier === 'none') {
        pass("Membership is free tier (expected for new user)");
    } else {
        warn("New user has non-free plan: " . json_encode($planTier));
    }
} else {
    fail("GET /membership failed: HTTP {$code}");
}

// 5.2 Order creation validation
[$code, $order] = req('POST', '/orders', ['plan_id' => 99999], $token);
if ($code === 422) {
    pass('Order with invalid plan_id → 422 (validation works)');
} else {
    fail("Order validation failed to reject invalid plan_id: HTTP {$code}");
}

echo "\n━━━ PHASE 6: Admin Operations ━━━\n";

// Login as admin
[$code, $adminLogin] = req('POST', '/auth/login', [
    'email' => 'admin@aistory.dev',
    'password' => 'Admin123456',
]);
if ($code !== 200) {
    warn("Admin login failed (HTTP {$code}) — admin tests skipped. Seed admin user first.");
} else {
    $adminToken = $adminLogin['data']['token'];

    // 6.1 Admin dashboard
    [$code, $dash] = req('GET', '/admin/dashboard', null, $adminToken);
    if ($code === 200) pass('Admin dashboard accessible');
    else fail("Admin dashboard: HTTP {$code}");

    // 6.2 Admin views user list
    [$code, $users] = req('GET', '/admin/users', null, $adminToken);
    if ($code === 200 && isset($users['data']['data'])) {
        pass('Admin users list returns paginated data');
    } else {
        fail("Admin users list: HTTP {$code} or wrong format");
    }

    // 6.3 Admin reviews our journey user's work
    if ($workId) {
        [$code, $reviewList] = req('GET', '/admin/review/works?status=pending_review', null, $adminToken);
        if ($code === 200) pass('Admin review works list → 200');
        else fail("Admin review works: HTTP {$code}");
    }
}

echo "\n━━━ PHASE 7: Security & Edge Cases ━━━\n";

// 7.1 Access another user's work (should get 404, not 403)
if ($workId) {
    // Register second user
    [$code, $u2] = req('POST', '/auth/register', [
        'name' => 'User2', 'email' => 'user2_' . uniqid() . '@test.ai',
        'password' => 'Test123456!', 'password_confirmation' => 'Test123456!',
    ]);
    $token2 = $u2['data']['token'] ?? null;
    if ($token2) {
        [$code, $otherWork] = req('GET', "/works/{$workId}", null, $token2);
        if ($code === 404) pass('Accessing another user work → 404 (user scoping works)');
        else fail("Accessing another user work: HTTP {$code}, expected 404");
    }
}

// 7.2 Expired/invalid token
[$code, $bad] = req('GET', '/auth/me', null, 'invalid_token_12345');
if ($code === 401) pass('Invalid token → 401');
else fail("Invalid token: HTTP {$code}, expected 401");

// 7.3 Clean up — delete test work
if ($workId) {
    [$code, $del] = req('DELETE', "/works/{$workId}", null, $token);
    if ($code === 200 || $code === 204) pass('Delete work → 200/204 (cleanup)');
    else warn("Delete work cleanup: HTTP {$code}");
}

// 7.4 Delete model config
if ($configId) {
    [$code, $delCfg] = req('DELETE', "/user/model-configs/{$configId}", null, $token);
    if ($code === 200 || $code === 204) pass('Delete model config → 200/204 (cleanup)');
    else warn("Delete config cleanup: HTTP {$code}");
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Summary
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo "\n" . str_repeat('=', 60) . "\n";
echo "  Results: {$passed} passed, {$failed} failed";
if ($warnings) echo ", " . count($warnings) . " warnings";
echo "\n" . str_repeat('=', 60) . "\n";

if ($warnings) {
    echo "\nWarnings:\n";
    foreach ($warnings as $i => $w) echo "  " . ($i + 1) . ". {$w}\n";
}

exit($failed > 0 ? 1 : 0);
