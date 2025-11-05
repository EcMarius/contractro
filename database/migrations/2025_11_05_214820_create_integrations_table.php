<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('type'); // anaf, sms, email, storage, etc.
            $table->string('provider'); // anaf_efactura, twilio, mailgun, s3, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config'); // API keys, credentials, settings
            $table->json('metadata')->nullable(); // Additional data
            $table->boolean('is_active')->default(false);
            $table->boolean('is_test_mode')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('sync_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['type', 'provider']);
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('action'); // sync, send, validate, etc.
            $table->string('status'); // success, failed, pending
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('integrations');
    }
};
