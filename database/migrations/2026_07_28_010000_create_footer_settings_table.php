<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Main Footer');
            $table->string('status')->default('active');
            $table->unsignedTinyInteger('columns')->default(3);
            $table->timestamps();
        });

        // Insert a default row so the settings screen works immediately.
        DB::table('footer_settings')->insert([
            'name' => 'Main Footer',
            'status' => 'active',
            'columns' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_settings');
    }
};
