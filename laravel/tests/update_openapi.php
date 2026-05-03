<?php
$file = 'd:/办公/manju/laravel/public/openapi.json';
$json = json_decode(file_get_contents($file), true);

// ─── Health endpoints ───
$json['paths']['/health'] = ['get' => [
    'tags' => ['System'], 'summary' => 'Health check', 'operationId' => 'healthCheck',
    'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => [
        'schema' => ['type' => 'object', 'properties' => [
            'status' => ['type' => 'string', 'example' => 'ok'],
            'service' => ['type' => 'string'],
            'version' => ['type' => 'string'],
            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
        ]]]]]]
]];

$json['paths']['/health/deep'] = ['get' => [
    'tags' => ['System'], 'summary' => 'Deep health check — verifies DB, Redis, FastAPI',
    'operationId' => 'healthDeep',
    'responses' => [
        '200' => ['description' => 'All services healthy'],
        '503' => ['description' => 'One or more services degraded'],
    ]
]];

// ─── Auth flows ───
$json['paths']['/auth/forgot-password'] = ['post' => [
    'tags' => ['Auth'], 'summary' => 'Request password reset link', 'operationId' => 'forgotPassword',
    'requestBody' => ['required' => true, 'content' => ['application/json' => [
        'schema' => ['type' => 'object', 'required' => ['email'],
            'properties' => ['email' => ['type' => 'string', 'format' => 'email']]]]]],
    'responses' => ['200' => ['description' => 'Reset link sent'], '422' => ['description' => 'Validation error']]
]];

$json['paths']['/auth/reset-password'] = ['post' => [
    'tags' => ['Auth'], 'summary' => 'Reset password with token', 'operationId' => 'resetPassword',
    'requestBody' => ['required' => true, 'content' => ['application/json' => [
        'schema' => ['type' => 'object', 'required' => ['email', 'password', 'token'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 8],
                'token' => ['type' => 'string'],
            ]]]]],
    'responses' => ['200' => ['description' => 'Password reset'], '422' => ['description' => 'Validation error']]
]];

$json['paths']['/auth/change-password'] = ['post' => [
    'tags' => ['Auth'], 'summary' => 'Change password (authenticated)', 'operationId' => 'changePassword',
    'security' => [['bearerAuth' => []]],
    'requestBody' => ['required' => true, 'content' => ['application/json' => [
        'schema' => ['type' => 'object', 'required' => ['current_password', 'new_password'],
            'properties' => [
                'current_password' => ['type' => 'string'],
                'new_password' => ['type' => 'string', 'minLength' => 8],
            ]]]]],
    'responses' => [
        '200' => ['description' => 'Password changed'],
        '401' => ['description' => 'Unauthenticated'],
        '403' => ['description' => 'Wrong current password'],
    ]
]];

// ─── Admin Plans (full CRUD + status) ───
$json['paths']['/admin/plans'] = [
    'get' => ['tags' => ['Admin — Plans'], 'summary' => 'List all plans', 'operationId' => 'adminListPlans',
        'security' => [['bearerAuth' => []]], 'responses' => ['200' => ['description' => 'Plans list']]],
    'post' => ['tags' => ['Admin — Plans'], 'summary' => 'Create plan', 'operationId' => 'adminCreatePlan',
        'security' => [['bearerAuth' => []]], 'responses' => ['201' => ['description' => 'Plan created']]],
];

$json['paths']['/admin/plans/{id}'] = [
    'put' => ['tags' => ['Admin — Plans'], 'summary' => 'Update plan', 'operationId' => 'adminUpdatePlan',
        'security' => [['bearerAuth' => []]],
        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
        'responses' => ['200' => ['description' => 'Plan updated']]],
    'delete' => ['tags' => ['Admin — Plans'], 'summary' => 'Delete plan', 'operationId' => 'adminDeletePlan',
        'security' => [['bearerAuth' => []]],
        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
        'responses' => ['204' => ['description' => 'Plan deleted']]],
];

$json['paths']['/admin/plans/{id}/status'] = ['put' => [
    'tags' => ['Admin — Plans'], 'summary' => 'Toggle plan active/inactive', 'operationId' => 'adminTogglePlanStatus',
    'security' => [['bearerAuth' => []]],
    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
    'responses' => ['200' => ['description' => 'Status toggled']]
]];

// ─── Admin Roles ───
$json['paths']['/admin/roles'] = ['get' => [
    'tags' => ['Admin — Roles'], 'summary' => 'List roles', 'operationId' => 'adminListRoles',
    'security' => [['bearerAuth' => []]], 'responses' => ['200' => ['description' => 'Roles list']]
]];

$json['paths']['/admin/roles/{id}'] = ['put' => [
    'tags' => ['Admin — Roles'], 'summary' => 'Update role', 'operationId' => 'adminUpdateRole',
    'security' => [['bearerAuth' => []]],
    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
    'responses' => ['200' => ['description' => 'Role updated']]
]];

// ─── Admin Review ───
$json['paths']['/admin/review/works'] = ['get' => [
    'tags' => ['Admin — Review'], 'summary' => 'List works pending review', 'operationId' => 'adminReviewWorks',
    'security' => [['bearerAuth' => []]], 'responses' => ['200' => ['description' => 'Works pending review']]
]];

$json['paths']['/admin/review/works/{id}/approve'] = ['put' => [
    'tags' => ['Admin — Review'], 'summary' => 'Approve work', 'operationId' => 'adminApproveWork',
    'security' => [['bearerAuth' => []]],
    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
    'responses' => ['200' => ['description' => 'Work approved']]
]];

$json['paths']['/admin/review/works/{id}/reject'] = ['put' => [
    'tags' => ['Admin — Review'], 'summary' => 'Reject work', 'operationId' => 'adminRejectWork',
    'security' => [['bearerAuth' => []]],
    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
    'responses' => ['200' => ['description' => 'Work rejected']]
]];

// ─── Missing apiResource GET /{id} (show) ───
$showMap = [
    'voice-library' => ['Voice', 'Voices'],
    'action-templates' => ['ActionTemplate', 'Action Templates'],
    'sensitive-words' => ['SensitiveWord', 'Sensitive Words'],
    'banners' => ['Banner', 'Banners'],
    'templates' => ['Template', 'Templates'],
    'assets' => ['Asset', 'Assets'],
];
foreach ($showMap as $res => [$tag, $tagPlural]) {
    $path = '/admin/' . $res . '/{id}';
    if (!isset($json['paths'][$path]['get'])) {
        $json['paths'][$path] = array_merge(
            ['get' => [
                'tags' => ['Admin — ' . $tagPlural],
                'summary' => 'Get ' . $res . ' detail',
                'operationId' => 'adminGet' . $tag,
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => $tag . ' detail']],
            ]],
            $json['paths'][$path] // preserve existing PUT/DELETE
        );
    }
}

// Sort paths alphabetically
$paths = $json['paths'];
ksort($paths);
$json['paths'] = $paths;

file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

// Count
$newJson = json_decode(file_get_contents($file), true);
$count = 0;
foreach ($newJson['paths'] as $p => $m) {
    foreach ($m as $method => $spec) $count++;
}
echo 'Updated openapi.json — ' . $count . ' total endpoints (was ~74)' . "\n";
