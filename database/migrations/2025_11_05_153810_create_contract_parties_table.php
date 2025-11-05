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
        Schema::create('contract_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->enum('party_type', ['client', 'provider', 'witness', 'other'])->default('client');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_series')->nullable(); // ID card series
            $table->string('id_number')->nullable(); // ID card number
            $table->text('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_cui')->nullable(); // Company fiscal code
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('signature_data')->nullable(); // Store signature image path, verification details
            $table->timestamps();

            $table->index('contract_id');
            $table->index('email');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_parties');
    }
};
