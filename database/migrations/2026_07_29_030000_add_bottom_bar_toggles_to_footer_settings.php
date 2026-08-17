<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('footer_settings', function (Blueprint $table) {
            $table->boolean('show_company_name')->default(true)->after('social_links');
            $table->boolean('show_tagline')->default(true)->after('show_company_name');
            $table->boolean('show_social_links')->default(true)->after('show_tagline');
            $table->boolean('show_copyright')->default(true)->after('show_social_links');
        });
    }

    public function down(): void
    {
        Schema::table('footer_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_company_name',
                'show_tagline',
                'show_social_links',
                'show_copyright',
            ]);
        });
    }
};
