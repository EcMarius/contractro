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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // If signed by a registered user
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_role')->nullable(); // Client, Vendor, Witness, etc.
            $table->text('signature_data')->nullable(); // Base64 encoded signature image or typed signature
            $table->enum('signature_type', ['drawn', 'typed', 'uploaded', 'electronic'])->default('drawn');
            $table->enum('status', ['pending', 'signed', 'declined', 'expired'])->default('pending');
            $table->integer('signing_order')->default(0); // For sequential signing
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('verification_token')->nullable(); // For email verification before signing
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index('signer_email');
            $table->index('verification_token');
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
