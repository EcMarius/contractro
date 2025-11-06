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
        // Contracts table indexes
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('created_at', 'idx_contracts_created_at');
            $table->index('contract_value', 'idx_contracts_value');
            $table->index('effective_date', 'idx_contracts_effective_date');
            $table->index('expiration_date', 'idx_contracts_expiration_date');
            $table->index(['status', 'created_at'], 'idx_contracts_status_created');
            $table->index(['user_id', 'status'], 'idx_contracts_user_status');
        });

        // Contract signatures table indexes
        Schema::table('contract_signatures', function (Blueprint $table) {
            $table->index('signer_email', 'idx_signatures_email');
            $table->index('created_at', 'idx_signatures_created_at');
            $table->index('signed_at', 'idx_signatures_signed_at');
            $table->index('expires_at', 'idx_signatures_expires_at');
            $table->index(['status', 'created_at'], 'idx_signatures_status_created');
        });

        // Contract templates table indexes
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->index('category', 'idx_templates_category');
            $table->index('is_public', 'idx_templates_public');
            $table->index('is_system', 'idx_templates_system');
            $table->index('usage_count', 'idx_templates_usage');
            $table->index(['user_id', 'category'], 'idx_templates_user_category');
        });

        // Contract comments table indexes
        Schema::table('contract_comments', function (Blueprint $table) {
            $table->index('is_resolved', 'idx_comments_resolved');
            $table->index(['contract_id', 'created_at'], 'idx_comments_contract_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contracts_created_at');
            $table->dropIndex('idx_contracts_value');
            $table->dropIndex('idx_contracts_effective_date');
            $table->dropIndex('idx_contracts_expiration_date');
            $table->dropIndex('idx_contracts_status_created');
            $table->dropIndex('idx_contracts_user_status');
        });

        // Contract signatures
        Schema::table('contract_signatures', function (Blueprint $table) {
            $table->dropIndex('idx_signatures_email');
            $table->dropIndex('idx_signatures_created_at');
            $table->dropIndex('idx_signatures_signed_at');
            $table->dropIndex('idx_signatures_expires_at');
            $table->dropIndex('idx_signatures_status_created');
        });

        // Contract templates
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropIndex('idx_templates_category');
            $table->dropIndex('idx_templates_public');
            $table->dropIndex('idx_templates_system');
            $table->dropIndex('idx_templates_usage');
            $table->dropIndex('idx_templates_user_category');
        });

        // Contract comments
        Schema::table('contract_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_resolved');
            $table->dropIndex('idx_comments_contract_created');
        });
    }
};
