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
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // Service, Legal, Employment, Sales, etc.
            $table->longText('content');
            $table->json('variables')->nullable(); // Array of variable definitions {name, label, type, required, default}
            $table->json('metadata')->nullable(); // Tags, industry, use case, etc.
            $table->boolean('is_public')->default(false); // Public templates available to all users
            $table->boolean('is_system')->default(false); // System templates (cannot be deleted)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Creator for custom templates
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('usage_count')->default(0);
            $table->decimal('price', 10, 2)->nullable(); // If selling templates
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('is_public');
            $table->index(['user_id', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
