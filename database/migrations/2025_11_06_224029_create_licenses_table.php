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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('product_name')->default('Contract Platform');
            $table->string('product_version')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired', 'cancelled'])->default('active');
            $table->enum('type', ['trial', 'monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('check_count')->default(0);
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable(); // Store additional info like server details
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['domain', 'status']);
            $table->index('license_key');
            $table->index('user_id');
        });

        // License check logs table
        Schema::create('license_check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained()->onDelete('set null');
            $table->string('license_key');
            $table->string('domain');
            $table->string('ip_address');
            $table->boolean('is_valid')->default(false);
            $table->string('check_type')->default('api'); // api, manual, cron
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->index('license_key');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_check_logs');
        Schema::dropIfExists('licenses');
    }
};
