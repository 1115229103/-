<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System settings (KV store)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->text('value')->nullable();
            $table->string('group', 32)->default('general');
            $table->string('type', 16)->default('string')->comment('string|int|bool|json');
            $table->string('description', 256)->nullable();
            $table->timestamps();
        });

        // Watermark config
        Schema::create('watermark_configs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->comment('visible|blind|both');
            $table->string('position', 16)->default('bottom_right')->comment('top_left|top_right|bottom_left|bottom_right|center');
            $table->string('image_url', 512)->nullable()->comment('Visible watermark image');
            $table->unsignedInteger('opacity')->default(50)->comment('0-100');
            $table->unsignedInteger('width_percent')->default(20)->comment('Watermark width vs video width %');
            $table->string('text', 128)->nullable()->comment('Watermark text');
            $table->string('text_color', 8)->default('#FFFFFF');
            $table->boolean('blind_enabled')->default(false)->comment('Enable blind watermark');
            $table->timestamps();
        });

        // Payment configs
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 16)->comment('wechat|alipay|stripe');
            $table->string('app_id', 128)->nullable();
            $table->text('api_key')->nullable()->comment('Encrypted');
            $table->text('private_key')->nullable()->comment('Encrypted');
            $table->text('public_key')->nullable();
            $table->string('notify_url', 512)->nullable();
            $table->string('return_url', 512)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        // Email configs
        Schema::create('email_configs', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 16)->default('smtp');
            $table->string('host', 128)->nullable();
            $table->unsignedInteger('port')->default(587);
            $table->string('encryption', 8)->default('tls');
            $table->string('username', 128)->nullable();
            $table->text('password')->nullable()->comment('Encrypted');
            $table->string('from_address', 128)->nullable();
            $table->string('from_name', 128)->nullable();
            $table->timestamps();
        });

        // SMS configs
        Schema::create('sms_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->comment('aliyun|tencent|yunpian');
            $table->string('access_key', 128)->nullable();
            $table->text('access_secret')->nullable()->comment('Encrypted');
            $table->string('sign_name', 64)->nullable();
            $table->string('template_code', 64)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        // Storage configs
        Schema::create('storage_configs', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 16)->default('local')->comment('local|oss|cos|s3');
            $table->string('endpoint', 256)->nullable();
            $table->string('access_key', 128)->nullable();
            $table->text('access_secret')->nullable()->comment('Encrypted');
            $table->string('bucket', 128)->nullable();
            $table->string('region', 64)->nullable();
            $table->string('cdn_domain', 256)->nullable();
            $table->timestamps();
        });

        // Message templates
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 128);
            $table->string('channel', 16)->comment('email|sms|push|in_app');
            $table->string('title', 256)->nullable();
            $table->text('content')->nullable();
            $table->json('variables')->nullable();
            $table->timestamps();
        });

        // Push logs
        Schema::create('push_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 16);
            $table->string('message_code', 64)->nullable();
            $table->string('target', 256)->nullable();
            $table->text('content')->nullable();
            $table->string('status', 16)->default('sent')->comment('sent|failed');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Operation logs
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module', 32)->comment('Admin module');
            $table->string('action', 16)->comment('create|update|delete|status');
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // Backups
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->comment('full|db|files');
            $table->string('file_path', 512)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('status', 16)->default('pending')->comment('pending|processing|completed|failed');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Seed default watermark
        DB::table('watermark_configs')->insert([
            'type' => 'visible',
            'position' => 'bottom_right',
            'text' => 'AIStory',
            'opacity' => 50,
            'width_percent' => 20,
            'blind_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed default storage
        DB::table('storage_configs')->insert([
            'driver' => 'local',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('operation_logs');
        Schema::dropIfExists('push_logs');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('storage_configs');
        Schema::dropIfExists('sms_configs');
        Schema::dropIfExists('email_configs');
        Schema::dropIfExists('payment_configs');
        Schema::dropIfExists('watermark_configs');
        Schema::dropIfExists('system_settings');
    }
};
