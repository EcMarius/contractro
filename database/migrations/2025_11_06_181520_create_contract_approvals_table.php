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
        Schema::create('contract_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->integer('step_number')->default(1);
            $table->string('step_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('due_at')->nullable(); // For escalation
            $table->boolean('is_required')->default(true); // Can step be skipped?
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'step_number']);
            $table->index(['approver_id', 'status']);
        });

        // Add approval status to contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->enum('approval_status', ['not_required', 'pending', 'approved', 'rejected'])->default('not_required')->after('status');
            $table->integer('current_approval_step')->nullable()->after('approval_status');
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'current_approval_step']);
        });

        Schema::dropIfExists('contract_approvals');
    }
};
