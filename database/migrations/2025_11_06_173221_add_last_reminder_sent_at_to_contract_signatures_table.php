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
        Schema::table('contract_signatures', function (Blueprint $table) {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('declined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_signatures', function (Blueprint $table) {
            $table->dropColumn('last_reminder_sent_at');
        });
    }
};
