<?php

namespace Whilesmart\Invoices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Customers\Models\Customer;
use Whilesmart\Invoices\Database\Factories\InvoiceFactory;
use Whilesmart\Invoices\Enums\InvoiceStatus;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'date',
        'paid_at' => 'date',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('invoices.invoices_table', 'invoices');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('position');
    }

    public function recalculate(): self
    {
        $subtotal = (int) $this->lineItems()->sum('amount_cents');
        $this->subtotal_cents = $subtotal;
        $this->total_cents = $subtotal - $this->discount_cents + $this->tax_cents;

        return $this;
    }

    public function balanceCents(): int
    {
        return max(0, $this->total_cents - $this->amount_paid_cents);
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
