<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('order_no', 64)->unique();
            $table->string('payment_method', 16)->comment('wechat|alipay|stripe');
            $table->unsignedInteger('amount_cny');
            $table->string('status', 16)->default('pending')->comment('pending|paid|refunded|cancelled');
            $table->string('transaction_id', 128)->nullable();
            $table->json('payment_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Visual styles
        Schema::create('visual_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('category', 16)->comment('image|video|both');
            $table->string('prompt_keyword', 256)->comment('Prompt keyword for this style');
            $table->string('preview_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Voice library
        Schema::create('voice_library', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('provider', 64);
            $table->string('provider_voice_id', 128)->comment('Provider-side voice ID');
            $table->string('gender', 8)->comment('男|女|中性');
            $table->string('language', 32)->default('zh-CN');
            $table->string('style', 64)->nullable()->comment('温柔|活泼|沉稳|霸气|萝莉|御姐');
            $table->string('sample_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Action templates (from docs/镜头提示词.md + docs/动作提示词参考.md)
        Schema::create('action_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('category', 32)->comment('打斗|魔法|日常|追逐|情感|特效|运镜');
            $table->text('prompt_cn')->nullable();
            $table->text('prompt_en')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Banners
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 128);
            $table->string('image_url', 512);
            $table->string('link_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Content templates
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('category', 32)->comment('script|storyboard|style');
            $table->text('content')->nullable();
            $table->string('preview_url', 512)->nullable();
            $table->boolean('is_premium')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Assets (BGM, SFX, images)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('type', 16)->comment('bgm|sfx|image|video');
            $table->string('file_url', 512);
            $table->string('mime_type', 64)->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        // Sensitive words
        Schema::create('sensitive_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 128);
            $table->string('category', 16)->comment('political|adult|violence|spam|custom');
            $table->unsignedInteger('severity')->default(1)->comment('1=flag, 2=block');
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_words');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('action_templates');
        Schema::dropIfExists('voice_library');
        Schema::dropIfExists('visual_styles');
        Schema::dropIfExists('orders');
    }
};
