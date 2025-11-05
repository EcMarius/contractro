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
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category'); // service_contract, rental_contract, collaboration_contract, employment_contract, etc.
            $table->text('template')->nullable(); // Default template content
            $table->json('fields_schema')->nullable(); // Custom fields schema
            $table->string('numbering_format')->default('{prefix}-{number}/{year}'); // e.g., "CNT-{number}/{year}"
            $table->boolean('is_system_default')->default(false); // System-provided types
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
