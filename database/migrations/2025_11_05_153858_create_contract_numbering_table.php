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
        Schema::create('contract_numbering', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_type_id')->constrained()->onDelete('cascade');
            $table->year('year');
            $table->string('prefix')->default('CNT');
            $table->unsignedInteger('last_number')->default(0);
            $table->string('format')->default('{prefix}-{number}/{year}');
            $table->json('reserved_numbers')->nullable(); // Array of reserved/skipped numbers
            $table->timestamps();

            $table->unique(['company_id', 'contract_type_id', 'year']);
            $table->index('company_id');
            $table->index('contract_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_numbering');
    }
};
