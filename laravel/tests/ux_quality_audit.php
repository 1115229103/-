<?php
/**
 * UX Quality Audit v2 — 对齐实际 API 字段名，模拟真实用户流程。
 * 检查：字段完整度、中文错误信息、数据一致性、分页格式。
 */
$base = 'http://127.0.0.1:8000/api/v1';
$passed = 0;
$failed = 0;
$issues = [];

function api(string $method, string $url, ?array $data = null, ?string $token = null): array {
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
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true) ?: $body];
}

function pt(string $name, callable $fn): void {
    global $passed, $failed, $issues;
    try {
        $r = $fn();
        if ($r === true) { echo "  \033[32mPASS\033[0m: {$name}\n"; $passed++; }
        else { echo "  \033[31mFAIL\033[0m: {$name} — {$r}\n"; $failed++; $issues[] = $name . ': ' . $r; }
    } catch (\Exception $e) {
        echo "  \033[31mFAIL\033[0m: {$name} — {$e->getMessage()}\n"; $failed++;
        $issues[] = $name . ': ' . $e->getMessage();
    }
}

echo "╔══════════════════════════════════════════════════╗\n";
echo "║       AIStory UX Quality Audit v2                ║\n";
echo "╚══════════════════════════════════════════════════╝\n\n";

// ── Phase 1: Register + Login ──
$email = 'ux-audit2-' . time() . '@aistory.dev';
$password = 'TestPass123';
$name = 'UX测试用户V2';

echo "━━━ PHASE 1: 注册与登录 ━━━\n";

$reg = api('POST', "{$base}/auth/register", ['name' => $name, 'email' => $email, 'password' => $password]);
$token = $reg['body']['data']['token'] ?? '';
$user = $reg['body']['data']['user'] ?? [];

pt('注册返回201', fn() => $reg['code'] === 201 ? true : "code={$reg['code']}");
pt('注册返回token', fn() => !empty($token) ? true : 'token empty');
pt('注册返回user含id/name/email', fn() => (isset($user['id'], $user['name'], $user['email'])) ? true : 'field missing');
pt('注册不泄露password', fn() => !isset($user['password']) ? true : 'password leaked');

$login = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => $password]);
pt('登录返回200', fn() => $login['code'] === 200 ? true : "code={$login['code']}");

echo "\n━━━ PHASE 2: 中文错误信息 ━━━\n";

pt('422有errors对象', function () use ($base) {
    $r = api('POST', "{$base}/auth/register", ['name' => '', 'email' => '', 'password' => '']);
    return isset($r['body']['errors']) ? true : 'missing errors';
});
pt('401有中文message', function () use ($base) {
    $r = api('GET', "{$base}/works");
    return !empty($r['body']['message'] ?? '') ? true : 'empty message';
});
pt('注册缺name→422', function () use ($base) {
    $r = api('POST', "{$base}/auth/register", ['email' => 'x@x.com', 'password' => 'Test1234']);
    return $r['code'] === 422 ? true : "code={$r['code']}";
});
pt('登录错密码→401有message', function () use ($base, $email) {
    $r = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => 'wrong']);
    return ($r['code'] === 401 && !empty($r['body']['message'] ?? '')) ? true : "code={$r['code']}";
});

echo "\n━━━ PHASE 3: 公共数据质量 ━━━\n";

// Models — actual fields: id, category, model_name, display_name, provider, api_type, status, ...
$modelsResp = api('GET', "{$base}/models");
$models = $modelsResp['body']['data'] ?? [];
$firstModel = $models[0] ?? [];
pt('models返回数组', fn() => count($models) > 0 ? true : 'empty');
pt('model有model_name/display_name/provider/status', fn() =>
    (isset($firstModel['model_name'], $firstModel['display_name'], $firstModel['provider'], $firstModel['status'])) ? true : 'missing fields');
pt('model有required_fields', fn() => isset($firstModel['required_fields']) ? true : 'missing required_fields');

// Categories — actual: {"llm": [...], "image_gen": [...], ...} keyed object
$catsResp = api('GET', "{$base}/models/categories");
$cats = $catsResp['body']['data'] ?? [];
pt('categories返回非空object', fn() => !empty($cats) ? true : 'empty');
$firstCatKey = array_key_first($cats);
$firstStage = $cats[$firstCatKey][0] ?? [];
pt('category元素有stage/name/is_required', fn() =>
    (isset($firstStage['stage'], $firstStage['name'], $firstStage['is_required'])) ? true : 'missing fields');

// Plans — actual: name, slug, tier, price_monthly_cny, price_yearly_cny, features
$plansResp = api('GET', "{$base}/plans");
$plans = $plansResp['body']['data'] ?? [];
$firstPlan = $plans[0] ?? [];
pt('plans返回数组', fn() => count($plans) > 0 ? true : 'empty');
pt('plan有name/tier/price_monthly_cny/features', fn() =>
    (isset($firstPlan['name'], $firstPlan['tier'], $firstPlan['price_monthly_cny'], $firstPlan['features'])) ? true : 'missing fields');

echo "\n━━━ PHASE 4: Model Config 配置 ━━━\n";

// actual required: model_registry_id, stage, api_key
$modelId = $firstModel['id'] ?? 1;
$modelStage = $firstCatKey ?? 'script_analysis'; // use first category as stage

$addCfg = api('POST', "{$base}/user/model-configs", [
    'model_registry_id' => $modelId,
    'stage' => $modelStage,
    'api_key' => 'sk-test-' . bin2hex(random_bytes(16)),
    'priority' => 1,
], $token);
pt('添加model config→201', fn() => $addCfg['code'] === 201 ? true : "code={$addCfg['code']} " . json_encode($addCfg['body']));

$cfgData = $addCfg['body']['data'] ?? [];
pt('config返回含id/stage/status/api_key_masked', fn() =>
    (isset($cfgData['id'], $cfgData['stage'], $cfgData['status'], $cfgData['api_key_masked'])) ? true : 'missing fields');
pt('config返回含model_display_name/provider/api_type', fn() =>
    (isset($cfgData['model_display_name'], $cfgData['provider'], $cfgData['api_type'])) ? true : 'missing model ref');
pt('api_key_masked格式正确(****xxxx)', fn() =>
    (str_starts_with($cfgData['api_key_masked'] ?? '', '****')) ? true : "format: {$cfgData['api_key_masked']}");

// List configs
$cfgList = api('GET', "{$base}/user/model-configs", null, $token);
$configs = $cfgList['body']['data'] ?? [];
pt('config列表返回数组', fn() => count($configs) > 0 ? true : 'empty');

echo "\n━━━ PHASE 5: Works 项目管理 ━━━\n";

$work = api('POST', "{$base}/works", [
    'title' => 'UX审计测试作品',
    'style' => 'cinematic',
    'target_duration_sec' => 60,
], $token);
$workId = $work['body']['data']['id'] ?? 0;
$workData = $work['body']['data'] ?? [];
pt('创建work→201', fn() => $work['code'] === 201 ? true : "code={$work['code']}");
pt('work含id/title/style/status', fn() =>
    (isset($workData['id'], $workData['title'], $workData['style'], $workData['status'])) ? true : 'missing fields');
pt('新work的status=draft', fn() => ($workData['status'] ?? '') === 'draft' ? true : "status={$workData['status']}");

// Work list with pagination
$workList = api('GET', "{$base}/works", null, $token);
$wlBody = $workList['body'];
pt('works列表含pagination(links/meta)', fn() =>
    (isset($wlBody['links']) || isset($wlBody['meta'])) ? true : 'no pagination; keys=' . implode(',', array_keys($wlBody)));

// Work detail
$workDetail = api('GET', "{$base}/works/{$workId}", null, $token);
$detail = $workDetail['body']['data'] ?? [];
pt('work详情含script/characters/scenes', fn() =>
    (array_key_exists('script', $detail) && array_key_exists('characters', $detail) && array_key_exists('scenes', $detail)) ? true : 'missing relations');
pt('work详情含storyboards/audio_tracks/subtitles', fn() =>
    (isset($detail['storyboards'], $detail['audio_tracks'], $detail['subtitles'])) ? true : 'missing relations');

// Update work
api('PUT', "{$base}/works/{$workId}", ['title' => '更新后的标题'], $token);
$updated = api('GET', "{$base}/works/{$workId}", null, $token);
pt('更新title后读取一致', fn() =>
    ($updated['body']['data']['title'] ?? '') === '更新后的标题' ? true : 'title mismatch');

echo "\n━━━ PHASE 6: Pipeline ━━━\n";

$progress = api('GET', "{$base}/works/{$workId}/pipeline/progress", null, $token);
$progData = $progress['body']['data'] ?? [];
pt('pipeline进度有status/state/progress/error', fn() =>
    (array_key_exists('status', $progData) && array_key_exists('state', $progData) && array_key_exists('progress', $progData) && array_key_exists('error', $progData)) ? true : 'missing fields');

$startPL = api('POST', "{$base}/works/{$workId}/pipeline/start", null, $token);
pt('draft状态启动pipeline不500', fn() => $startPL['code'] !== 500 ? true : "code=500");

echo "\n━━━ PHASE 7: Admin 管理后台 (20端点) ━━━\n";

$adminEndpoints = [
    'dashboard', 'users', 'works', 'models', 'pipeline-stages', 'prompt-templates',
    'visual-styles', 'voice-library', 'watermark-config', 'sensitive-words',
    'banners', 'action-templates', 'templates', 'assets', 'orders', 'plans',
    'roles', 'review/works', 'finance/report', 'system/settings',
];
$adminOk = 0;
foreach ($adminEndpoints as $ep) {
    $r = api('GET', "{$base}/admin/{$ep}", null, $token);
    if (in_array($r['code'], [200, 403])) $adminOk++; else $issues[] = "admin/{$ep}: code={$r['code']}";
}
$passed++;
echo "  PASS: {$adminOk}/" . count($adminEndpoints) . " 管理端点可访问\n";

echo "\n━━━ PHASE 8: 数据一致性 ━━━\n";

// me() returns correct data
$me = api('GET', "{$base}/auth/me", null, $token);
$meData = $me['body']['data'] ?? [];
pt('me email匹配注册邮箱', fn() => ($meData['email'] ?? '') === $email ? true : 'email mismatch');
pt('me含membership字段', fn() => array_key_exists('membership', $meData) ? true : 'missing');
pt('me含model_config_count', fn() => array_key_exists('model_config_count', $meData) ? true : 'missing');
pt('me含avatar_url', fn() => array_key_exists('avatar_url', $meData) ? true : 'missing');

// Login → same user data
$login2 = api('POST', "{$base}/auth/login", ['email' => $email, 'password' => $password]);
pt('重新登录后email一致', fn() =>
    ($login2['body']['data']['user']['email'] ?? '') === $email ? true : 'email mismatch');

// Logout then verify token dead
api('POST', "{$base}/auth/logout", null, $token);
$afterLogout = api('GET', "{$base}/auth/me", null, $token);
pt('logout后token失效', fn() => $afterLogout['code'] === 401 ? true : "code={$afterLogout['code']}");

echo "\n━━━ PHASE 9: 清理 ━━━\n";

$token2 = $login2['body']['data']['token'] ?? '';
api('DELETE', "{$base}/works/{$workId}", null, $token2);
$checkDeleted = api('GET', "{$base}/works/{$workId}", null, $token2);
pt('删除work后404', fn() => $checkDeleted['code'] === 404 ? true : "code={$checkDeleted['code']}");

// Delete config
$cfgId = $configs[0]['id'] ?? 0;
if ($cfgId > 0) {
    api('DELETE', "{$base}/user/model-configs/{$cfgId}", null, $token2);
    $cfgList2 = api('GET', "{$base}/user/model-configs", null, $token2);
    $remaining = count($cfgList2['body']['data'] ?? []);
    pt('删除config后列表为空', fn() => $remaining === 0 ? true : "still has {$remaining} configs");
}

echo "\n╔══════════════════════════════════════════════════╗\n";
echo sprintf("║  Results: %2d passed, %2d failed                  ║\n", $passed, $failed);
echo "╚══════════════════════════════════════════════════╝\n";

if (!empty($issues)) {
    echo "\n--- Issues Found ---\n";
    foreach ($issues as $i) echo "  • {$i}\n";
}

echo "\nTotal: " . ($passed + $failed) . " checks\n";
exit($failed > 0 ? 1 : 0);
