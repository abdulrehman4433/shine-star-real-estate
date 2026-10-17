<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Payment options move from block-level to per-size: each block's dynamic key/value rows
     *  are appended to its FIRST size option (one size option = one payment option section),
     *  the block-level column is dropped, and blocks gain a video_urls JSON column so a block's
     *  videos can be a mix of uploaded files and external (YouTube/Vimeo) links. */
    public function up(): void
    {
        Schema::table('project_blocks', function (Blueprint $table) {
            $table->json('video_urls')->nullable()->after('plot_sizes');
        });

        DB::table('project_blocks')->whereNotNull('payment_options')->orderBy('id')->each(function ($block) {
            $payment = json_decode($block->payment_options, true);

            if (! is_array($payment) || $payment === []) {
                return;
            }

            $sizes = json_decode($block->plot_sizes, true) ?: [];

            if ($sizes === []) {
                return; // Block-level rows with no size to move onto are discarded (clean-slate design).
            }

            $sizes[0]['payment_options'] = array_merge($sizes[0]['payment_options'] ?? [], $payment);

            DB::table('project_blocks')->where('id', $block->id)->update([
                'plot_sizes' => json_encode(array_values($sizes)),
            ]);
        });

        Schema::table('project_blocks', function (Blueprint $table) {
            $table->dropColumn('payment_options');
        });
    }

    /** Reverse the migrations: first non-empty size's payment rows go back to block level. */
    public function down(): void
    {
        Schema::table('project_blocks', function (Blueprint $table) {
            $table->json('payment_options')->nullable()->after('description');
        });

        DB::table('project_blocks')->whereNotNull('plot_sizes')->orderBy('id')->each(function ($block) {
            $sizes = json_decode($block->plot_sizes, true) ?: [];

            $firstPaid = collect($sizes)->first(fn ($row) => ! empty($row['payment_options']));
            $payment = $firstPaid['payment_options'] ?? null;

            if (! is_array($payment) || $payment === []) {
                return;
            }

            foreach ($sizes as $index => $row) {
                unset($sizes[$index]['payment_options']);
            }

            DB::table('project_blocks')->where('id', $block->id)->update([
                'payment_options' => json_encode(array_values($payment)),
                'plot_sizes' => json_encode(array_values($sizes)),
            ]);
        });

        Schema::table('project_blocks', function (Blueprint $table) {
            $table->dropColumn('video_urls');
        });
    }
};
