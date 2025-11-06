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
        // Add transfer tracking columns to licenses table
        Schema::table('licenses', function (Blueprint $table) {
            $table->integer('transfer_count')->default(0)->after('check_count');
            $table->integer('max_transfers')->default(3)->after('transfer_count');
            $table->timestamp('last_transferred_at')->nullable()->after('max_transfers');
        });

        // Create license transfers history table
        Schema::create('license_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            $table->string('old_domain');
            $table->string('new_domain');
            $table->foreignId('initiated_by_user_id')->constrained('users');
            $table->string('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('admin_approved')->default(false);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->index(['license_id', 'transferred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_transfers');

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['transfer_count', 'max_transfers', 'last_transferred_at']);
        });
    }
};
