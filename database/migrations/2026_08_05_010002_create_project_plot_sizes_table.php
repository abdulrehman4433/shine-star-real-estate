<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_plot_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_block_id')->nullable()->constrained('project_blocks')->nullOnDelete();
            $table->decimal('size_value', 8, 2);
            $table->string('unit')->default('marla');
            $table->string('category')->nullable();
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_plot_sizes');
    }
};
