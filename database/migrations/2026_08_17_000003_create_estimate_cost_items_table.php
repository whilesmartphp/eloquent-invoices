<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Internal cost breakdown behind an estimate: what it costs to deliver,
        // optionally attributed to one of the estimate's line items. Not
        // customer-facing; it feeds margin, not the estimate total.
        Schema::create(config('invoices.estimate_cost_items_table', 'estimate_cost_items'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained(config('invoices.estimates_table', 'estimates'))->cascadeOnDelete();
            $table->unsignedBigInteger('estimate_line_item_id')->nullable();
            $table->string('category')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->bigInteger('unit_cost_cents')->default(0);
            $table->bigInteger('amount_cents')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['estimate_id']);
            $table->index(['estimate_line_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('invoices.estimate_cost_items_table', 'estimate_cost_items'));
    }
};
