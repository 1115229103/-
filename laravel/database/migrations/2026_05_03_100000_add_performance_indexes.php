<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // orders.status + paid_at: FinanceController revenue queries
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);
        });

        // works.status: ReviewController status filtering
        Schema::table('works', function (Blueprint $table) {
            $table->index('status');
        });

        // works.user_id already indexed (FK), but (user_id, status) composite
        // for "my drafts" queries
        Schema::table('works', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
        });
    }
};
