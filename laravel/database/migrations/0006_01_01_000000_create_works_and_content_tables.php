<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 256);
            $table->string('status', 16)->default('draft')->comment('draft|parsing|storyboarding|generating|compositing|completed|failed');
            $table->string('style', 64)->nullable()->comment('Visual style preference');
            $table->unsignedInteger('target_duration_sec')->nullable();
            $table->string('pipeline_state', 32)->nullable()->comment('Current pipeline stage');
            $table->json('pipeline_progress')->nullable()->comment('Per-stage progress');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->longText('content');
            $table->longText('continuation')->nullable();
            $table->json('parsed_data')->nullable()->comment('Stage 1 output: characters/scenes/emotions/plot');
            $table->timestamps();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('gender', 8)->nullable()->comment('男|女|未知');
            $table->string('age_range', 32)->nullable();
            $table->text('appearance')->nullable();
            $table->text('personality')->nullable();
            $table->string('role_type', 16)->nullable()->comment('主角|配角|龙套');
            $table->string('voice_id', 128)->nullable()->comment('TTS voice binding');
            $table->json('reference_images')->nullable()->comment('Reference image URLs');
            $table->timestamps();
        });

        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('location', 256)->nullable();
            $table->string('time_of_day', 8)->nullable()->comment('昼|夜|黄昏|清晨');
            $table->boolean('indoor')->default(true);
            $table->text('atmosphere')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('storyboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->unsignedInteger('shot_number');
            $table->string('shot_type', 16)->nullable()->comment('远景|全景|中景|近景|特写|大特写');
            $table->string('camera_movement', 32)->nullable()->comment('Camera movement type');
            $table->unsignedInteger('duration_sec')->default(5);
            $table->foreignId('scene_id')->nullable()->constrained('scenes')->nullOnDelete();
            $table->json('characters_in_frame')->nullable();
            $table->text('action_description')->nullable();
            $table->text('dialogue')->nullable();
            $table->string('emotion', 16)->nullable();
            $table->string('transition_to_next', 16)->nullable()->comment('切|淡入淡出|闪白|滑动');
            $table->text('notes')->nullable();
            $table->string('image_url')->nullable()->comment('Stage 4 output');
            $table->string('video_url')->nullable()->comment('Stage 7 output');
            $table->string('status', 16)->default('pending')->comment('pending|generating|completed|failed|skipped');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();
        });

        Schema::create('audio_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('storyboard_id')->nullable()->constrained('storyboards')->nullOnDelete();
            $table->string('type', 16)->comment('tts|bgm|sfx');
            $table->string('file_url')->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->float('volume')->default(1.0);
            $table->float('start_offset_sec')->default(0);
            $table->timestamps();
        });

        Schema::create('subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->string('format', 8)->default('srt')->comment('srt|ass|vtt');
            $table->longText('content')->nullable();
            $table->string('file_url')->nullable();
            $table->string('language', 8)->default('zh-CN');
            $table->timestamps();
        });

        Schema::create('export_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->string('status', 16)->default('pending')->comment('pending|processing|completed|failed');
            $table->string('resolution', 8)->default('1080p')->comment('720p|1080p|4k|8k');
            $table->string('format', 8)->default('mp4')->comment('mp4|mov|webm');
            $table->string('codec', 8)->default('h264')->comment('h264|h265|vp9');
            $table->unsignedInteger('progress')->default(0);
            $table->string('output_url')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_tasks');
        Schema::dropIfExists('subtitles');
        Schema::dropIfExists('audio_tracks');
        Schema::dropIfExists('storyboards');
        Schema::dropIfExists('scenes');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('scripts');
        Schema::dropIfExists('works');
    }
};
