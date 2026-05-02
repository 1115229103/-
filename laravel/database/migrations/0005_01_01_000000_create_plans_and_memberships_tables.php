<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('slug', 64)->unique();
            $table->string('tier', 16)->comment('free|basic|pro|enterprise');
            $table->unsignedInteger('price_monthly_cny')->default(0);
            $table->unsignedInteger('price_yearly_cny')->default(0);
            $table->json('features')->comment('Feature limits JSON');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('status', 16)->default('active')->comment('active|expired|cancelled');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('billing_cycle', 8)->default('monthly')->comment('monthly|yearly');
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        // Seed default plans
        $plans = [
            [
                'name' => '免费版', 'slug' => 'free', 'tier' => 'free',
                'price_monthly_cny' => 0, 'price_yearly_cny' => 0,
                'features' => json_encode([
                    'max_resolution' => '720p',
                    'watermark' => true,
                    'max_projects' => 3,
                    'max_storyboards' => 15,
                    'max_characters' => 5,
                    'storage_gb' => 1,
                    'max_duration_sec' => 60,
                    'templates' => 'basic',
                    'transitions' => 5,
                    'voices' => 3,
                    'subtitle_styles' => 3,
                    'batch_generation' => false,
                    'batch_export' => false,
                    'super_resolution' => false,
                    'api_access' => false,
                    'private_deploy' => false,
                    'support' => 'community',
                ]),
                'sort_order' => 0,
            ],
            [
                'name' => '基础版', 'slug' => 'basic', 'tier' => 'basic',
                'price_monthly_cny' => 3900, 'price_yearly_cny' => 39900,
                'features' => json_encode([
                    'max_resolution' => '1080p',
                    'watermark' => false,
                    'max_projects' => 30,
                    'max_storyboards' => 80,
                    'max_characters' => 30,
                    'storage_gb' => 20,
                    'max_duration_sec' => 300,
                    'templates' => 'all',
                    'transitions' => 20,
                    'voices' => 50,
                    'subtitle_styles' => 30,
                    'batch_generation' => false,
                    'batch_export' => false,
                    'super_resolution' => false,
                    'api_access' => false,
                    'private_deploy' => false,
                    'support' => 'email',
                ]),
                'sort_order' => 1,
            ],
            [
                'name' => '专业版', 'slug' => 'pro', 'tier' => 'pro',
                'price_monthly_cny' => 19900, 'price_yearly_cny' => 199900,
                'features' => json_encode([
                    'max_resolution' => '4k',
                    'watermark' => false,
                    'max_projects' => 200,
                    'max_storyboards' => 300,
                    'max_characters' => 100,
                    'storage_gb' => 200,
                    'max_duration_sec' => 1200,
                    'templates' => 'all',
                    'transitions' => 'all_custom',
                    'voices' => 'all',
                    'subtitle_styles' => 'all',
                    'batch_generation' => true,
                    'batch_export' => true,
                    'super_resolution' => true,
                    'api_access' => false,
                    'private_deploy' => false,
                    'support' => 'priority',
                ]),
                'sort_order' => 2,
            ],
            [
                'name' => '企业版', 'slug' => 'enterprise', 'tier' => 'enterprise',
                'price_monthly_cny' => 0, 'price_yearly_cny' => 0,
                'features' => json_encode([
                    'max_resolution' => '8k',
                    'watermark' => false,
                    'max_projects' => null,
                    'max_storyboards' => null,
                    'max_characters' => null,
                    'storage_gb' => 2000,
                    'max_duration_sec' => null,
                    'templates' => 'all_custom',
                    'transitions' => 'all_custom',
                    'voices' => 'all_custom',
                    'subtitle_styles' => 'all_custom',
                    'batch_generation' => true,
                    'batch_export' => true,
                    'super_resolution' => true,
                    'api_access' => true,
                    'private_deploy' => true,
                    'support' => 'dedicated',
                ]),
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->insert(array_merge($plan, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('plans');
    }
};
