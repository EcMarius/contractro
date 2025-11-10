<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        };

        // Contracts table indexes
        Schema::table('contracts', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('contracts', 'idx_contracts_value')) {
                $table->index('contract_value', 'idx_contracts_value');
            }
            if (!$indexExists('contracts', 'idx_contracts_status_created')) {
                $table->index(['status', 'created_at'], 'idx_contracts_status_created');
            }
        });

        // Contract signatures table indexes
        Schema::table('contract_signatures', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('contract_signatures', 'idx_signatures_email')) {
                $table->index('signer_email', 'idx_signatures_email');
            }
            if (!$indexExists('contract_signatures', 'idx_signatures_created_at')) {
                $table->index('created_at', 'idx_signatures_created_at');
            }
            if (!$indexExists('contract_signatures', 'idx_signatures_signed_at')) {
                $table->index('signed_at', 'idx_signatures_signed_at');
            }
            if (!$indexExists('contract_signatures', 'idx_signatures_expires_at')) {
                $table->index('expires_at', 'idx_signatures_expires_at');
            }
            if (!$indexExists('contract_signatures', 'idx_signatures_status_created')) {
                $table->index(['status', 'created_at'], 'idx_signatures_status_created');
            }
        });

        // Contract templates table indexes
        Schema::table('contract_templates', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('contract_templates', 'idx_templates_category')) {
                $table->index('category', 'idx_templates_category');
            }
            if (!$indexExists('contract_templates', 'idx_templates_public')) {
                $table->index('is_public', 'idx_templates_public');
            }
            if (!$indexExists('contract_templates', 'idx_templates_system')) {
                $table->index('is_system', 'idx_templates_system');
            }
            if (!$indexExists('contract_templates', 'idx_templates_usage')) {
                $table->index('usage_count', 'idx_templates_usage');
            }
            if (!$indexExists('contract_templates', 'idx_templates_user_category')) {
                $table->index(['user_id', 'category'], 'idx_templates_user_category');
            }
        });

        // Contract comments table indexes
        Schema::table('contract_comments', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('contract_comments', 'idx_comments_resolved')) {
                $table->index('is_resolved', 'idx_comments_resolved');
            }
            if (!$indexExists('contract_comments', 'idx_comments_contract_created')) {
                $table->index(['contract_id', 'created_at'], 'idx_comments_contract_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        };

        // Contracts table
        Schema::table('contracts', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('contracts', 'idx_contracts_value')) {
                $table->dropIndex('idx_contracts_value');
            }
            if ($indexExists('contracts', 'idx_contracts_status_created')) {
                $table->dropIndex('idx_contracts_status_created');
            }
        });

        // Contract signatures
        Schema::table('contract_signatures', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('contract_signatures', 'idx_signatures_email')) {
                $table->dropIndex('idx_signatures_email');
            }
            if ($indexExists('contract_signatures', 'idx_signatures_created_at')) {
                $table->dropIndex('idx_signatures_created_at');
            }
            if ($indexExists('contract_signatures', 'idx_signatures_signed_at')) {
                $table->dropIndex('idx_signatures_signed_at');
            }
            if ($indexExists('contract_signatures', 'idx_signatures_expires_at')) {
                $table->dropIndex('idx_signatures_expires_at');
            }
            if ($indexExists('contract_signatures', 'idx_signatures_status_created')) {
                $table->dropIndex('idx_signatures_status_created');
            }
        });

        // Contract templates
        Schema::table('contract_templates', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('contract_templates', 'idx_templates_category')) {
                $table->dropIndex('idx_templates_category');
            }
            if ($indexExists('contract_templates', 'idx_templates_public')) {
                $table->dropIndex('idx_templates_public');
            }
            if ($indexExists('contract_templates', 'idx_templates_system')) {
                $table->dropIndex('idx_templates_system');
            }
            if ($indexExists('contract_templates', 'idx_templates_usage')) {
                $table->dropIndex('idx_templates_usage');
            }
            if ($indexExists('contract_templates', 'idx_templates_user_category')) {
                $table->dropIndex('idx_templates_user_category');
            }
        });

        // Contract comments
        Schema::table('contract_comments', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('contract_comments', 'idx_comments_resolved')) {
                $table->dropIndex('idx_comments_resolved');
            }
            if ($indexExists('contract_comments', 'idx_comments_contract_created')) {
                $table->dropIndex('idx_comments_contract_created');
            }
        });
    }
};
