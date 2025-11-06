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
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->longText('content');
            $table->json('variables')->nullable();
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->text('change_summary')->nullable();
            $table->string('pdf_path')->nullable(); // Path to stored PDF version
            $table->json('metadata')->nullable(); // Storage disk, encryption status, file size, etc.
            $table->timestamp('created_at');

            $table->unique(['contract_id', 'version_number']);
            $table->index('contract_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};
