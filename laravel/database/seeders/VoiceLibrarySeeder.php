<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoiceLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $voices = [
            // ElevenLabs voices
            ['name' => 'Rachel (美式女声)', 'provider' => 'ElevenLabs', 'provider_voice_id' => '21m00Tcm4TlvDq8ikWAM', 'gender' => '女', 'language' => 'en-US', 'style' => '温柔', 'sort_order' => 1],
            ['name' => 'Adam (美式男声)', 'provider' => 'ElevenLabs', 'provider_voice_id' => 'pNInz6obpgDQGcFmaJgB', 'gender' => '男', 'language' => 'en-US', 'style' => '沉稳', 'sort_order' => 2],
            ['name' => 'Antoni (英式男声)', 'provider' => 'ElevenLabs', 'provider_voice_id' => 'ErXwobaYiN019PkySvjV', 'gender' => '男', 'language' => 'en-GB', 'style' => '沉稳', 'sort_order' => 3],
            ['name' => 'Grace (美式女声)', 'provider' => 'ElevenLabs', 'provider_voice_id' => 'oWAxZDx7w5VEj9dCyTzz', 'gender' => '女', 'language' => 'en-US', 'style' => '活泼', 'sort_order' => 4],
            // AliCloud TTS voices
            ['name' => '阿里云-温柔女声', 'provider' => '阿里云', 'provider_voice_id' => 'xiaoyun', 'gender' => '女', 'language' => 'zh-CN', 'style' => '温柔', 'sort_order' => 5],
            ['name' => '阿里云-标准男声', 'provider' => '阿里云', 'provider_voice_id' => 'xiaogang', 'gender' => '男', 'language' => 'zh-CN', 'style' => '沉稳', 'sort_order' => 6],
            ['name' => '阿里云-活泼女声', 'provider' => '阿里云', 'provider_voice_id' => 'xiaoxue', 'gender' => '女', 'language' => 'zh-CN', 'style' => '活泼', 'sort_order' => 7],
            // Tencent TTS voices
            ['name' => '腾讯云-标准女声', 'provider' => '腾讯云', 'provider_voice_id' => '101001', 'gender' => '女', 'language' => 'zh-CN', 'style' => '温柔', 'sort_order' => 8],
            ['name' => '腾讯云-标准男声', 'provider' => '腾讯云', 'provider_voice_id' => '101002', 'gender' => '男', 'language' => 'zh-CN', 'style' => '沉稳', 'sort_order' => 9],
            // Azure voices
            ['name' => 'Azure-晓晓(女)', 'provider' => 'Microsoft', 'provider_voice_id' => 'zh-CN-XiaoxiaoNeural', 'gender' => '女', 'language' => 'zh-CN', 'style' => '温柔', 'sort_order' => 10],
            ['name' => 'Azure-云希(男)', 'provider' => 'Microsoft', 'provider_voice_id' => 'zh-CN-YunxiNeural', 'gender' => '男', 'language' => 'zh-CN', 'style' => '沉稳', 'sort_order' => 11],
            ['name' => 'Azure-晓悠(女)', 'provider' => 'Microsoft', 'provider_voice_id' => 'zh-CN-XiaoyouNeural', 'gender' => '女', 'language' => 'zh-CN', 'style' => '活泼', 'sort_order' => 12],
            ['name' => '讯飞-小燕(女)', 'provider' => '科大讯飞', 'provider_voice_id' => 'xiaoyan', 'gender' => '女', 'language' => 'zh-CN', 'style' => '温柔', 'sort_order' => 13],
            ['name' => '讯飞-小峰(男)', 'provider' => '科大讯飞', 'provider_voice_id' => 'aisjiuxu', 'gender' => '男', 'language' => 'zh-CN', 'style' => '沉稳', 'sort_order' => 14],
            ['name' => 'OpenAI-Alloy', 'provider' => 'OpenAI', 'provider_voice_id' => 'alloy', 'gender' => '中性', 'language' => 'en-US', 'style' => '沉稳', 'sort_order' => 15],
            ['name' => 'OpenAI-Nova', 'provider' => 'OpenAI', 'provider_voice_id' => 'nova', 'gender' => '女', 'language' => 'en-US', 'style' => '温柔', 'sort_order' => 16],
        ];

        foreach ($voices as $voice) {
            DB::table('voice_library')->insertOrIgnore(array_merge($voice, [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
