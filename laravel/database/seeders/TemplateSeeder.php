<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Script templates
            [
                'name' => '短剧开场模板',
                'category' => 'script',
                'content' => "在{location}，{character_name}正{action}，突然{event}。\n{character_name}决定{decision}，一场{genre}的故事就此展开。",
                'is_premium' => false,
                'sort_order' => 1,
            ],
            [
                'name' => '悬疑推理模板',
                'category' => 'script',
                'content' => "深夜，{location}传来一声{sound}。{character_name}循声而去，发现了{clue}。\n这似乎与{days}天前发生的{mystery}有关。",
                'is_premium' => false,
                'sort_order' => 2,
            ],
            [
                'name' => '情感反转模板',
                'category' => 'script',
                'content' => "{character_a}和{character_b}在{location}相遇，原以为{false_belief}。\n直到{revelation}，才发现{truth}。",
                'is_premium' => false,
                'sort_order' => 3,
            ],
            [
                'name' => '古风仙侠模板',
                'category' => 'script',
                'content' => "{realm}界，{character_name}修{realm}千年，只为{goal}。\n岂料{twist}，{character_name}不得不{action}。",
                'is_premium' => true,
                'sort_order' => 4,
            ],
            [
                'name' => '都市职场模板',
                'category' => 'script',
                'content' => "在{company}，{character_name}面临{challenge}。\n同事{colleague}的{betrayal}让一切雪上加霜。\n{character_name}决定{resolution}。",
                'is_premium' => true,
                'sort_order' => 5,
            ],
            [
                'name' => '科幻未来模板',
                'category' => 'script',
                'content' => "公元{year}年，{technology}改变了世界。\n{character_name}在一次{accident}中发现了{secret}，\n这关乎全人类的{stake}。",
                'is_premium' => true,
                'sort_order' => 6,
            ],
            // Storyboard templates
            [
                'name' => '经典三幕式',
                'category' => 'storyboard',
                'content' => "第一幕（建置）：介绍{character}和{world}，触发{inciting_event}\n第二幕（对抗）：{character}面对{obstacles}，经历{midpoint}\n第三幕（解决）：{climax}，{resolution}",
                'is_premium' => false,
                'sort_order' => 7,
            ],
            [
                'name' => '快节奏动作',
                'category' => 'storyboard',
                'content' => "开场{action_opening}（5秒）→ 追逐{chase}（10秒）→ 对决{confrontation}（8秒）→ 反转{twist}（5秒）→ 收尾{ending}（7秒）",
                'is_premium' => false,
                'sort_order' => 8,
            ],
            [
                'name' => '情感慢镜头',
                'category' => 'storyboard',
                'content' => "特写{character_face}（3秒）→ 回忆闪回{flashback}（8秒）→ 环境{environment}（4秒）→ 表情变化{expression}（3秒）→ 留白{silence}（5秒）",
                'is_premium' => true,
                'sort_order' => 9,
            ],
            [
                'name' => '产品展示',
                'category' => 'storyboard',
                'content' => "产品全景{product_wide}（3秒）→ 细节特写{detail}（5秒）→ 使用场景{usage}（6秒）→ 效果对比{comparison}（4秒）→ 品牌logo{brand}（2秒）",
                'is_premium' => true,
                'sort_order' => 10,
            ],
            // Style templates
            [
                'name' => '抖音竖屏风格',
                'category' => 'style',
                'content' => '{"aspect_ratio":"9:16","duration_target":30,"transition":"cut","text_overlay":true,"music_style":"trending"}',
                'is_premium' => false,
                'sort_order' => 11,
            ],
            [
                'name' => 'B站横屏风格',
                'category' => 'style',
                'content' => '{"aspect_ratio":"16:9","duration_target":120,"transition":"fade","text_overlay":true,"music_style":"anime"}',
                'is_premium' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'YouTube专业风格',
                'category' => 'style',
                'content' => '{"aspect_ratio":"16:9","duration_target":300,"transition":"dissolve","text_overlay":true,"music_style":"cinematic","resolution":"4K"}',
                'is_premium' => true,
                'sort_order' => 13,
            ],
            [
                'name' => '小红书图文风格',
                'category' => 'style',
                'content' => '{"aspect_ratio":"3:4","duration_target":15,"transition":"slide","text_overlay":true,"music_style":"lofi","resolution":"1080P"}',
                'is_premium' => false,
                'sort_order' => 14,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('templates')->insertOrIgnore(array_merge($template, [
                'preview_url' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
