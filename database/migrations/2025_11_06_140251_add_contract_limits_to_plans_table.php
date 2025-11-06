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
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_contracts')->default(-1)->after('description'); // -1 = unlimited
            $table->integer('max_contract_templates')->default(10)->after('max_contracts');
            $table->integer('max_signatures_per_month')->default(50)->after('max_contract_templates');
            $table->integer('max_contract_value')->nullable()->after('max_signatures_per_month'); // Max contract value in dollars
            $table->boolean('enable_esignature')->default(true)->after('max_contract_value');
            $table->boolean('enable_ai_contract_generation')->default(false)->after('enable_esignature');
            $table->boolean('enable_contract_analytics')->default(false)->after('enable_ai_contract_generation');
            $table->boolean('enable_contract_collaboration')->default(false)->after('enable_contract_analytics');
            $table->boolean('enable_contract_version_control')->default(true)->after('enable_contract_collaboration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_contracts',
                'max_contract_templates',
                'max_signatures_per_month',
                'max_contract_value',
                'enable_esignature',
                'enable_ai_contract_generation',
                'enable_contract_analytics',
                'enable_contract_collaboration',
                'enable_contract_version_control',
            ]);
        });
    }
};
