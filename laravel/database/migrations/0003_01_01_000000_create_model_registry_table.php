<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_registry', function (Blueprint $table) {
            $table->id();
            $table->string('category', 32)->comment('llm|image_gen|consistency|image_enhance|image2video|video_enhance|tts|music|asr|moderation');
            $table->string('model_name', 128)->comment('Model identifier: claude-sonnet-4-6 / kling-image-o1');
            $table->string('display_name', 128)->comment('Display: Claude Sonnet 4.6');
            $table->string('provider', 64)->comment('Anthropic / OpenAI / Google / Kling');
            $table->string('api_type', 32)->comment('openai|anthropic|gemini|kling|elevenlabs|stability|replicate|bfl|azure|custom');
            $table->string('base_url', 512)->comment('API base URL');
            $table->string('request_path', 256)->nullable()->comment('API path e.g. /v1/chat/completions');
            $table->json('default_params')->nullable()->comment('Default params: {"temperature":0.7}');
            $table->json('required_fields')->nullable()->comment('Fields user must fill');
            $table->text('description')->nullable();
            $table->string('docs_url', 512)->nullable();
            $table->string('logo_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active')->comment('active|inactive');
            $table->timestamps();

            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_registry');
    }
};
