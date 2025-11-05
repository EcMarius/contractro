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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->foreignId('contract_type_id')->constrained()->onDelete('restrict');
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->longText('content'); // Contract content (HTML or plain text)
            $table->json('parties')->nullable(); // Store basic party info
            $table->enum('status', ['draft', 'pending', 'signed', 'active', 'expired', 'terminated'])->default('draft');
            $table->enum('signing_method', ['sms', 'handwritten', 'digital'])->default('sms');
            $table->decimal('value', 15, 2)->nullable(); // Contract value
            $table->string('currency', 3)->default('RON');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->json('metadata')->nullable(); // Additional custom fields
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('user_id');
            $table->index('contract_type_id');
            $table->index('contract_number');
            $table->index('status');
            $table->index('signed_at');
            $table->index('end_date');
            $table->index('created_at');
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
