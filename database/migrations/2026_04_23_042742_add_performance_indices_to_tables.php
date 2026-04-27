<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safer approach using raw SQL or checking presence
        $this->addIndexIfMissing('videos', 'category_id');
        $this->addIndexIfMissing('videos', 'download_status');
        $this->addIndexIfMissing('videos', 'is_premium');
        $this->addIndexIfMissing('videos', 'is_free_to_all');
        
        $this->addIndexIfMissing('categories', 'slug');
        $this->addIndexIfMissing('categories', 'order');
    }

    private function addIndexIfMissing($table, $column)
    {
        $indexName = "{$table}_{$column}_index";
        
        // Check if index exists (MySQL/MariaDB)
        $exists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        if (empty($exists)) {
            Schema::table($table, function (Blueprint $tableGroup) use ($column) {
                $tableGroup->index($column);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not strictly necessary to be perfect for down in this optimization pass
    }
};
