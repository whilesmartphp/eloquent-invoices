<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('invoices.line_items_table', 'invoice_line_items'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('description');
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit')->nullable();
            $table->bigInteger('unit_price_cents')->default(0);
            $table->bigInteger('amount_cents')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('invoices.line_items_table', 'invoice_line_items'));
    }
};
