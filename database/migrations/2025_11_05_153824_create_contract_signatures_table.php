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
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('party_id')->constrained('contract_parties')->onDelete('cascade');
            $table->enum('signature_method', ['sms', 'handwritten', 'digital'])->default('sms');
            $table->string('verification_code')->nullable(); // SMS verification code
            $table->string('verification_phone')->nullable();
            $table->boolean('code_verified')->default(false);
            $table->timestamp('code_sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional verification data
            $table->timestamps();

            $table->index('contract_id');
            $table->index('party_id');
            $table->index('verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
    }
};
