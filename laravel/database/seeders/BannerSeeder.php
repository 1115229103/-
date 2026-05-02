<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'AI视频创作新时代',
                'image_url' => '/images/banners/hero-1.jpg',
                'link_url' => '/register',
                'sort_order' => 1,
            ],
            [
                'title' => '专业版限时优惠',
                'image_url' => '/images/banners/promo-pro.jpg',
                'link_url' => '/pricing',
                'sort_order' => 2,
            ],
            [
                'title' => '一键生成动漫视频',
                'image_url' => '/images/banners/anime-style.jpg',
                'link_url' => '/works/new',
                'sort_order' => 3,
            ],
            [
                'title' => '企业版专属定制',
                'image_url' => '/images/banners/enterprise.jpg',
                'link_url' => '/enterprise',
                'sort_order' => 4,
            ],
            [
                'title' => '新功能：角色一致性增强',
                'image_url' => '/images/banners/consistency.jpg',
                'link_url' => '/docs/character-consistency',
                'sort_order' => 5,
            ],
        ];

        foreach ($banners as $banner) {
            DB::table('banners')->insertOrIgnore(array_merge($banner, [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
