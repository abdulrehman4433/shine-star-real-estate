<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('html')->nullable()->after('meta_description');
            $table->longText('css')->nullable()->after('html');
            $table->longText('js')->nullable()->after('css');
            $table->json('settings')->nullable()->after('js');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['html', 'css', 'js', 'settings']);
        });
    }
};
