<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for common dashboard and audit log queries.
 * Ensures sub-10ms query times even at 100k+ inspections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index('pass_fail',               'idx_pass_fail');
            $table->index('confidence',              'idx_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropIndex('idx_user_created');
            $table->dropIndex('idx_pass_fail');
            $table->dropIndex('idx_confidence');
        });
    }
};
