<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensitiveWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            // 政治敏感
            ['word' => '敏感词示例A', 'category' => 'political', 'severity' => 'high'],
            ['word' => '敏感词示例B', 'category' => 'political', 'severity' => 'high'],
            // 暴力恐怖
            ['word' => '暴力词示例A', 'category' => 'violence', 'severity' => 'high'],
            ['word' => '暴力词示例B', 'category' => 'violence', 'severity' => 'high'],
            // 色情
            ['word' => '色情词示例A', 'category' => 'porn', 'severity' => 'high'],
            ['word' => '色情词示例B', 'category' => 'porn', 'severity' => 'high'],
            // 赌博
            ['word' => '赌博词示例A', 'category' => 'gambling', 'severity' => 'medium'],
            // 广告
            ['word' => '广告词示例A', 'category' => 'ad', 'severity' => 'low'],
            ['word' => '广告词示例B', 'category' => 'ad', 'severity' => 'low'],
        ];

        foreach ($words as $word) {
            DB::table('sensitive_words')->insertOrIgnore(array_merge($word, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
