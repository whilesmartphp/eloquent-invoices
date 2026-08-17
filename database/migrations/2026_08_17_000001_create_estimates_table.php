<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('invoices.estimates_table', 'estimates'), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('number');
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->date('sent_at')->nullable();
            $table->date('accepted_at')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            $table->unsignedBigInteger('converted_invoice_id')->nullable();

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['owner_type', 'owner_id', 'number']);
            $table->index(['owner_type', 'owner_id', 'status']);
            $table->index(['owner_type', 'owner_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('invoices.estimates_table', 'estimates'));
    }
};
