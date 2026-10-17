<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('video_urls')->nullable()->after('video_url');
        });

        // Backfill: the legacy single video_url becomes the first (only) entry of video_urls,
        // so existing projects (e.g. Blue World City's YouTube link) show up in the new
        // multi-video editor immediately. video_url itself is kept in sync going forward
        // (first link) by Admin\Projects\Form::save().
        foreach (DB::table('projects')->whereNotNull('video_url')->whereNull('video_urls')->get() as $project) {
            DB::table('projects')->where('id', $project->id)->update([
                'video_urls' => json_encode([$project->video_url]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('video_urls');
        });
    }
};
