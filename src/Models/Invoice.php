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
use Whilesmart\Payments\Contracts\Payable;
use Whilesmart\Payments\Traits\HasPayments;

class Invoice extends Model implements Payable
{
    use HasFactory, HasPayments, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'issue_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'date',
        'paid_at' => 'date',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->number = static::generateNumber($invoice);
            }
        });
    }

    /**
     * Next per-owner invoice number, e.g. INV-00001. Derives the next sequence
     * from the highest existing number for this owner. The (owner, number)
     * unique index is the final guard against duplicates.
     */
    public static function generateNumber(Invoice $invoice): string
    {
        $prefix = (string) config('invoices.number_prefix', 'INV-');
        $length = (int) config('invoices.number_length', 5);

        $last = static::withTrashed()
            ->where('owner_type', $invoice->owner_type)
            ->where('owner_id', $invoice->owner_id)
            ->where('number', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(number) DESC')
            ->orderBy('number', 'DESC')
            ->value('number');

        $seq = $last ? (int) str_replace($prefix, '', $last) : 0;

        return $prefix.str_pad((string) ($seq + 1), $length, '0', STR_PAD_LEFT);
    }

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
