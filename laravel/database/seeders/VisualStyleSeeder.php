<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisualStyleSeeder extends Seeder
{
    public function run(): void
    {
        $styles = [
            ['name' => '写实', 'category' => 'image', 'prompt_keyword' => 'photorealistic, realistic, detailed', 'sort_order' => 1],
            ['name' => '动漫', 'category' => 'image', 'prompt_keyword' => 'anime style, manga style, 2D animation', 'sort_order' => 2],
            ['name' => '水墨', 'category' => 'image', 'prompt_keyword' => 'ink wash painting, Chinese ink style, watercolor', 'sort_order' => 3],
            ['name' => '3D渲染', 'category' => 'image', 'prompt_keyword' => '3D render, CGI, octane render, unreal engine', 'sort_order' => 4],
            ['name' => '像素', 'category' => 'image', 'prompt_keyword' => 'pixel art, 8-bit, retro game style', 'sort_order' => 5],
            ['name' => '赛博朋克', 'category' => 'image', 'prompt_keyword' => 'cyberpunk, neon lights, futuristic city', 'sort_order' => 6],
            ['name' => '油画', 'category' => 'image', 'prompt_keyword' => 'oil painting, impasto, fine art', 'sort_order' => 7],
            ['name' => '素描', 'category' => 'image', 'prompt_keyword' => 'pencil sketch, line art, hand-drawn', 'sort_order' => 8],
            ['name' => '水彩', 'category' => 'image', 'prompt_keyword' => 'watercolor painting, soft edges, flowing', 'sort_order' => 9],
            ['name' => '浮世绘', 'category' => 'image', 'prompt_keyword' => 'ukiyo-e, Japanese woodblock print style', 'sort_order' => 10],
            ['name' => '奇幻', 'category' => 'image', 'prompt_keyword' => 'fantasy art, magical, ethereal, mythical', 'sort_order' => 11],
            ['name' => '暗黑', 'category' => 'image', 'prompt_keyword' => 'dark fantasy, gothic, noir, moody lighting', 'sort_order' => 12],
        ];

        foreach ($styles as $style) {
            DB::table('visual_styles')->insertOrIgnore(array_merge($style, [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
