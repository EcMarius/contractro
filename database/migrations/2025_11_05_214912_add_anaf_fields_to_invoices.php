<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('anaf_upload_index')->nullable()->after('status');
            $table->string('anaf_status')->nullable()->after('anaf_upload_index'); // uploaded, validated, rejected
            $table->timestamp('anaf_uploaded_at')->nullable()->after('anaf_status');
            $table->timestamp('anaf_validated_at')->nullable()->after('anaf_uploaded_at');
            $table->json('anaf_response')->nullable()->after('anaf_validated_at');
            $table->text('anaf_error')->nullable()->after('anaf_response');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'anaf_upload_index',
                'anaf_status',
                'anaf_uploaded_at',
                'anaf_validated_at',
                'anaf_response',
                'anaf_error',
            ]);
        });
    }
};
