<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // BGM assets
        $bgm = [
            ['name' => '史诗交响', 'type' => 'bgm', 'tags' => ['epic', 'orchestral', 'cinematic'], 'duration_sec' => 120, 'sort_order' => 1],
            ['name' => '温馨钢琴', 'type' => 'bgm', 'tags' => ['warm', 'piano', 'emotional'], 'duration_sec' => 90, 'sort_order' => 2],
            ['name' => '悬疑氛围', 'type' => 'bgm', 'tags' => ['suspense', 'ambient', 'mystery'], 'duration_sec' => 60, 'sort_order' => 3],
            ['name' => '动作追逐', 'type' => 'bgm', 'tags' => ['action', 'fast', 'chase'], 'duration_sec' => 45, 'sort_order' => 4],
            ['name' => '古风国乐', 'type' => 'bgm', 'tags' => ['chinese', 'traditional', 'guqin'], 'duration_sec' => 100, 'sort_order' => 5],
            ['name' => '轻松电子', 'type' => 'bgm', 'tags' => ['electronic', 'upbeat', 'vlog'], 'duration_sec' => 75, 'sort_order' => 6],
            ['name' => '悲伤大提琴', 'type' => 'bgm', 'tags' => ['sad', 'cello', 'dramatic'], 'duration_sec' => 80, 'sort_order' => 7],
            ['name' => '浪漫吉他', 'type' => 'bgm', 'tags' => ['romantic', 'guitar', 'acoustic'], 'duration_sec' => 65, 'sort_order' => 8],
            ['name' => '科幻电子', 'type' => 'bgm', 'tags' => ['sci-fi', 'synth', 'futuristic'], 'duration_sec' => 55, 'sort_order' => 9],
            ['name' => '喜剧滑稽', 'type' => 'bgm', 'tags' => ['comedy', 'playful', 'cartoon'], 'duration_sec' => 40, 'sort_order' => 10],
        ];

        foreach ($bgm as $item) {
            DB::table('assets')->insertOrIgnore([
                'name' => $item['name'],
                'type' => $item['type'],
                'file_url' => '/assets/bgm/' . strtolower(str_replace(' ', '-', $item['name'])) . '.mp3',
                'mime_type' => 'audio/mpeg',
                'file_size_bytes' => random_int(500000, 5000000),
                'duration_sec' => $item['duration_sec'],
                'tags' => json_encode($item['tags']),
                'sort_order' => $item['sort_order'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // SFX assets
        $sfx = [
            ['name' => '剑出鞘', 'type' => 'sfx', 'tags' => ['weapon', 'metal', 'sharp'], 'duration_sec' => 2, 'sort_order' => 11],
            ['name' => '脚步声-草地', 'type' => 'sfx', 'tags' => ['footstep', 'grass', 'walking'], 'duration_sec' => 5, 'sort_order' => 12],
            ['name' => '雷声轰鸣', 'type' => 'sfx', 'tags' => ['thunder', 'weather', 'storm'], 'duration_sec' => 8, 'sort_order' => 13],
            ['name' => '关门声', 'type' => 'sfx', 'tags' => ['door', 'close', 'wood'], 'duration_sec' => 2, 'sort_order' => 14],
            ['name' => '心跳加速', 'type' => 'sfx', 'tags' => ['heartbeat', 'tension', 'suspense'], 'duration_sec' => 10, 'sort_order' => 15],
            ['name' => '玻璃碎裂', 'type' => 'sfx', 'tags' => ['glass', 'shatter', 'impact'], 'duration_sec' => 3, 'sort_order' => 16],
            ['name' => '钟声回荡', 'type' => 'sfx', 'tags' => ['bell', 'church', 'echo'], 'duration_sec' => 6, 'sort_order' => 17],
            ['name' => '雨声淅沥', 'type' => 'sfx', 'tags' => ['rain', 'ambient', 'weather'], 'duration_sec' => 30, 'sort_order' => 18],
            ['name' => '枪声', 'type' => 'sfx', 'tags' => ['gunshot', 'weapon', 'impact'], 'duration_sec' => 2, 'sort_order' => 19],
            ['name' => '汽车引擎', 'type' => 'sfx', 'tags' => ['car', 'engine', 'vehicle'], 'duration_sec' => 8, 'sort_order' => 20],
        ];

        foreach ($sfx as $item) {
            DB::table('assets')->insertOrIgnore([
                'name' => $item['name'],
                'type' => $item['type'],
                'file_url' => '/assets/sfx/' . strtolower(str_replace(' ', '-', $item['name'])) . '.wav',
                'mime_type' => 'audio/wav',
                'file_size_bytes' => random_int(50000, 500000),
                'duration_sec' => $item['duration_sec'],
                'tags' => json_encode($item['tags']),
                'sort_order' => $item['sort_order'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Image assets (placeholder/stock images)
        $images = [
            ['name' => '默认角色头像-男', 'type' => 'image', 'tags' => ['avatar', 'male', 'default'], 'sort_order' => 21],
            ['name' => '默认角色头像-女', 'type' => 'image', 'tags' => ['avatar', 'female', 'default'], 'sort_order' => 22],
            ['name' => '默认场景-都市', 'type' => 'image', 'tags' => ['scene', 'city', 'default'], 'sort_order' => 23],
            ['name' => '默认场景-自然', 'type' => 'image', 'tags' => ['scene', 'nature', 'default'], 'sort_order' => 24],
            ['name' => '默认场景-室内', 'type' => 'image', 'tags' => ['scene', 'indoor', 'default'], 'sort_order' => 25],
        ];

        foreach ($images as $item) {
            DB::table('assets')->insertOrIgnore([
                'name' => $item['name'],
                'type' => $item['type'],
                'file_url' => '/assets/images/' . strtolower(str_replace(' ', '-', $item['name'])) . '.png',
                'mime_type' => 'image/png',
                'file_size_bytes' => random_int(50000, 200000),
                'duration_sec' => null,
                'tags' => json_encode($item['tags']),
                'sort_order' => $item['sort_order'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
