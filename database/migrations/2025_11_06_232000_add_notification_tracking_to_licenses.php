<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            // Notification tracking to prevent duplicates
            $table->timestamp('notified_30_days_at')->nullable()->after('last_checked_at');
            $table->timestamp('notified_7_days_at')->nullable()->after('notified_30_days_at');
            $table->timestamp('notified_1_day_at')->nullable()->after('notified_7_days_at');
            $table->timestamp('notified_expired_at')->nullable()->after('notified_1_day_at');

            $table->index(['expires_at', 'notified_30_days_at']);
            $table->index(['expires_at', 'notified_7_days_at']);
            $table->index(['expires_at', 'notified_1_day_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['expires_at', 'notified_30_days_at']);
            $table->dropIndex(['expires_at', 'notified_7_days_at']);
            $table->dropIndex(['expires_at', 'notified_1_day_at']);

            $table->dropColumn([
                'notified_30_days_at',
                'notified_7_days_at',
                'notified_1_day_at',
                'notified_expired_at',
            ]);
        });
    }
};
