<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Make slug nullable so empty slug means home page.
            // MySQL allows multiple NULL values in a UNIQUE index, so
            // multiple draft pages with no slug are permitted, but only
            // one published page with null slug will be allowed via
            // application-level validation.
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Back to required and unique.  Pages with NULL slug will
            // first need a value assigned before rolling back.
            $table->string('slug')->unique()->change();
        });
    }
};
