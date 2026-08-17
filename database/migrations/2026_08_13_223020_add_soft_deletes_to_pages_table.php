<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->softDeletes();
        });

        // A soft-deleted page still occupies its row (and its slug) — a plain unique index doesn't
        // know about `deleted_at`, so it would block creating/publishing a new page with the same
        // slug as a trashed one still sitting in the table. Application-level validation
        // (Admin\Pages\Builder::saveMeta()) already enforces uniqueness among *active* pages
        // correctly (Eloquent's soft-delete global scope excludes trashed rows from those queries),
        // so the DB-level unique constraint is redundant and actively wrong once trashed rows can
        // exist — replaced with a plain (non-unique) index, kept only for lookup performance.
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->unique('slug');
            $table->dropSoftDeletes();
        });
    }
};
