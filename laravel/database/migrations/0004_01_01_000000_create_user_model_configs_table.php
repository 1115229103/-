<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_model_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('model_registry_id')->constrained('model_registry')->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('stage', 32)->comment('Pipeline stage identifier');
            $table->text('api_key')->comment('AES-256-GCM encrypted with User DEK');
            $table->json('custom_params')->nullable()->comment('User custom param overrides');
            $table->unsignedTinyInteger('priority')->default(0)->comment('0=primary, 1-N=fallback');
            $table->string('status', 16)->default('active')->comment('active|expired|error');
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category', 'stage', 'model_registry_id'], 'uk_user_model_stage');
            $table->index(['user_id', 'category', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_model_configs');
    }
};
