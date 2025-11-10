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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('contract_templates')->onDelete('set null');
            $table->unsignedBigInteger('lead_id')->nullable(); // Foreign key removed - leads table doesn't exist
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content');
            $table->json('variables')->nullable(); // Stores the values for template variables
            $table->enum('status', ['draft', 'pending_signature', 'partially_signed', 'signed', 'completed', 'cancelled', 'expired'])->default('draft');
            $table->decimal('contract_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('signed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Custom fields, tags, etc.
            $table->boolean('is_template')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('organization_id');
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
