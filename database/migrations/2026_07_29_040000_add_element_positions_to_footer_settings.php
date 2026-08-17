<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('footer_settings', function (Blueprint $table) {
            $table->string('position_company_name', 10)->default('left')->after('show_copyright');
            $table->string('position_tagline', 10)->default('left')->after('position_company_name');
            $table->string('position_social_links', 10)->default('right')->after('position_tagline');
            $table->string('position_copyright', 10)->default('right')->after('position_social_links');
        });
    }

    public function down(): void
    {
        Schema::table('footer_settings', function (Blueprint $table) {
            $table->dropColumn([
                'position_company_name',
                'position_tagline',
                'position_social_links',
                'position_copyright',
            ]);
        });
    }
};
