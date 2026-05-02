<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class SystemController extends Controller
{
    private const ALLOWED_KEYS = [
        'app_name' => 'string',
        'app_description' => 'string',
        'logo_url' => 'string',
        'favicon_url' => 'string',
        'primary_color' => 'string',
        'footer_text' => 'string',
        'max_upload_size_mb' => 'integer',
        'allowed_file_types' => 'string',
        'default_video_resolution' => 'string',
        'default_video_bitrate' => 'string',
        'maintenance_mode' => 'boolean',
        'registration_enabled' => 'boolean',
        'guest_browse_enabled' => 'boolean',
        'site_keywords' => 'string',
        'site_icp' => 'string',
        'contact_email' => 'string',
        'social_links' => 'json',
        'smtp_host' => 'string',
        'smtp_port' => 'integer',
        'smtp_encryption' => 'string',
        'sms_provider' => 'string',
        'storage_provider' => 'string',
        'oss_endpoint' => 'string',
        'oss_bucket' => 'string',
        'oss_region' => 'string',
        'verify_code_length' => 'integer',
        'verify_code_ttl' => 'integer',
        'login_attempt_limit' => 'integer',
        'ws_host' => 'string',
        'ws_port' => 'integer',
    ];

    public function index(): JsonResponse
    {
        return response()->json(['data' => SystemSetting::all()->pluck('value', 'key')]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->all();

        // Reject any key not in the whitelist
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, self::ALLOWED_KEYS)) {
                return response()->json(['errors' => ['key' => "Unknown or disallowed setting: {$key}"]], 422);
            }
        }

        // Build type-aware validation rules
        $rules = [];
        foreach ($data as $key => $value) {
            $type = self::ALLOWED_KEYS[$key];
            $rules[$key] = match ($type) {
                'integer' => 'integer',
                'boolean' => 'boolean',
                'json' => 'array',
                default => 'string',
            };
        }

        $v = Validator::make($data, $rules);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        foreach ($v->validated() as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string)$value]
            );
        }

        return response()->json(['status' => 'ok']);
    }
}
