<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_blocks', function (Blueprint $table) {
            // Dynamic key/value payment rows for this block, e.g.
            // [{"key": "Total Payment", "value": "300k"}, {"key": "Booking", "value": "40k"}]
            $table->json('payment_options')->nullable()->after('description');
            // Size/plot options offered inside this block, e.g.
            // [{"size_value": 5, "unit": "marla", "category": "Residential Plot", "bedrooms": null, "bathrooms": null}]
            $table->json('plot_sizes')->nullable()->after('payment_options');
        });

        // Move every existing plot-size row into its block's plot_sizes JSON.
        // Only descriptive fields are carried over — deliberately (clean-slate decision):
        // the old per-plot payment columns (total/booking/installments/...) are NOT migrated,
        // because payment now lives in the block-level dynamic payment_options KV section.
        $rowsByBlock = DB::table('project_plot_sizes')
            ->orderBy('project_block_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->groupBy('project_block_id');

        foreach ($rowsByBlock as $blockId => $plots) {
            if (! $blockId) {
                // Block-less plot sizes have nowhere to live in the new per-block model.
                // (None exist at the time of this migration; verified before writing it.)
                continue;
            }

            DB::table('project_blocks')->where('id', $blockId)->update([
                'plot_sizes' => json_encode($plots->map(fn ($plot) => [
                    'size_value' => (float) $plot->size_value,
                    'unit' => $plot->unit,
                    'category' => $plot->category,
                    'bedrooms' => $plot->bedrooms !== null ? (int) $plot->bedrooms : null,
                    'bathrooms' => $plot->bathrooms !== null ? (int) $plot->bathrooms : null,
                ])->values()->all()),
            ]);
        }

        Schema::dropIfExists('project_plot_sizes');
    }

    public function down(): void
    {
        // Recreates the schema only — the per-plot payment values were intentionally
        // dropped by up() and cannot be restored from here.
        Schema::create('project_plot_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_block_id')->nullable()->constrained('project_blocks')->nullOnDelete();
            $table->decimal('size_value', 8, 2);
            $table->string('unit')->default('marla');
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('total_price', 14, 2)->nullable();
            $table->decimal('booking_amount', 14, 2)->nullable();
            $table->decimal('confirmation_amount', 14, 2)->nullable();
            $table->decimal('installment_amount', 14, 2)->nullable();
            $table->unsignedInteger('installment_count')->nullable();
            $table->string('installment_frequency')->nullable();
            $table->decimal('possession_amount', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::table('project_blocks', function (Blueprint $table) {
            $table->dropColumn(['payment_options', 'plot_sizes']);
        });
    }
};
