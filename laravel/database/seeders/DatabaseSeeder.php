<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModelRegistrySeeder::class,
            VisualStyleSeeder::class,
            VoiceLibrarySeeder::class,
            ActionTemplateSeeder::class,
            SensitiveWordSeeder::class,
            BannerSeeder::class,
            TemplateSeeder::class,
            AssetSeeder::class,
        ]);
    }
}
