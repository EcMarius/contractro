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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->string('series')->default('FACT');
            $table->string('client_name');
            $table->string('client_cui')->nullable();
            $table->text('client_address')->nullable();
            $table->decimal('amount', 15, 2); // Subtotal before VAT
            $table->decimal('vat_rate', 5, 2)->default(19); // 19% VAT for Romania
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('RON');
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->json('items'); // Line items with description, quantity, unit_price
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('contract_id');
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
