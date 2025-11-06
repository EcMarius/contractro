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
        // Contract Folders
        Schema::create('contract_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('contract_folders')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
        });

        // Contract Tags
        Schema::create('contract_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#3B82F6');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });

        // Contract Folder Pivot
        Schema::create('contract_folder', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_folder_id')->constrained('contract_folders')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();

            $table->primary(['contract_id', 'contract_folder_id']);
        });

        // Contract Tag Pivot
        Schema::create('contract_tag', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_tag_id')->constrained('contract_tags')->onDelete('cascade');
            $table->timestamp('tagged_at')->useCurrent();

            $table->primary(['contract_id', 'contract_tag_id']);
        });

        // Add folder_id to contracts table for primary folder
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('contract_folders')->nullOnDelete();
            $table->boolean('is_favorite')->default(false)->after('status');
            $table->index(['user_id', 'folder_id']);
            $table->index('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn(['folder_id', 'is_favorite']);
        });

        Schema::dropIfExists('contract_tag');
        Schema::dropIfExists('contract_folder');
        Schema::dropIfExists('contract_tags');
        Schema::dropIfExists('contract_folders');
    }
};
