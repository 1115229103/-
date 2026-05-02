<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage', 32)->unique()->comment('Stage identifier');
            $table->string('name', 64)->comment('Display name');
            $table->string('category', 32)->comment('Model category: llm|image_gen|consistency|image_enhance|image2video|video_enhance|tts|music|asr|moderation');
            $table->boolean('is_required')->default(true)->comment('Must user configure a model?');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default 12 stages
        $stages = [
            ['stage' => 'script_analysis',    'name' => '文案解析',   'category' => 'llm',            'sort_order' => 1],
            ['stage' => 'storyboard',         'name' => '分镜规划',   'category' => 'llm',            'sort_order' => 2],
            ['stage' => 'continuation',       'name' => '文案续写',   'category' => 'llm',            'sort_order' => 3, 'is_required' => false],
            ['stage' => 'image_gen',          'name' => '画面生成',   'category' => 'image_gen',      'sort_order' => 4],
            ['stage' => 'consistency',        'name' => '角色一致',   'category' => 'consistency',    'sort_order' => 5, 'is_required' => false],
            ['stage' => 'image_enhance',      'name' => '图像后处理', 'category' => 'image_enhance',  'sort_order' => 6, 'is_required' => false],
            ['stage' => 'image2video',        'name' => '图生视频',   'category' => 'image2video',    'sort_order' => 7],
            ['stage' => 'video_enhance',      'name' => '视频增强',   'category' => 'video_enhance',  'sort_order' => 8, 'is_required' => false],
            ['stage' => 'tts',                'name' => 'AI配音',     'category' => 'tts',            'sort_order' => 9],
            ['stage' => 'music',              'name' => '背景音乐',   'category' => 'music',          'sort_order' => 10, 'is_required' => false],
            ['stage' => 'asr',                'name' => '字幕生成',   'category' => 'asr',            'sort_order' => 11, 'is_required' => false],
            ['stage' => 'moderation',         'name' => '敏感词检测', 'category' => 'moderation',     'sort_order' => 12, 'is_required' => false],
        ];

        foreach ($stages as $s) {
            DB::table('pipeline_stages')->insert(array_merge($s, [
                'is_required' => $s['is_required'] ?? true,
                'is_enabled'  => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
