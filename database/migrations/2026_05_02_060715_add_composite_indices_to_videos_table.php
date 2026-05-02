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
        Schema::table('videos', function (Blueprint $table) {
            $table->index(['category_id', 'created_at']);
            $table->index(['is_premium', 'created_at']);
            $table->index(['is_free_to_all', 'created_at']);
            $table->index(['download_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'created_at']);
            $table->dropIndex(['is_premium', 'created_at']);
            $table->dropIndex(['is_free_to_all', 'created_at']);
            $table->dropIndex(['download_status', 'created_at']);
        });
    }
};
