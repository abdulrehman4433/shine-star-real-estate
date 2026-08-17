<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not every project offers raw plots — Seventeen Villas sells built villas (2/3/4-bedroom
     * variants of the same footprint), ESMR Heights sells apartments — so a plot size needs to
     * optionally carry bedroom/bathroom counts, same shape as Property already has.
     */
    public function up(): void
    {
        Schema::table('project_plot_sizes', function (Blueprint $table) {
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('category');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
        });
    }

    public function down(): void
    {
        Schema::table('project_plot_sizes', function (Blueprint $table) {
            $table->dropColumn(['bedrooms', 'bathrooms']);
        });
    }
};
