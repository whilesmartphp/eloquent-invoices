<?php

namespace Whilesmart\Invoices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Whilesmart\Customers\Models\Customer;
use Whilesmart\Invoices\Database\Factories\EstimateFactory;
use Whilesmart\Invoices\Enums\EstimateStatus;
use Whilesmart\Invoices\Enums\InvoiceStatus;
use Whilesmart\Invoices\Events\EstimateAccepted;

class Estimate extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => EstimateStatus::class,
        'issue_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'date',
        'accepted_at' => 'date',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Estimate $estimate) {
            if (empty($estimate->number)) {
                $estimate->number = static::generateNumber($estimate);
            }
        });
    }

    public static function generateNumber(Estimate $estimate): string
    {
        $prefix = (string) config('invoices.estimate_number_prefix', 'EST-');
        $length = (int) config('invoices.number_length', 5);

        $last = static::withTrashed()
            ->where('owner_type', $estimate->owner_type)
            ->where('owner_id', $estimate->owner_id)
            ->where('number', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(number) DESC')
            ->orderBy('number', 'DESC')
            ->value('number');

        $seq = $last ? (int) str_replace($prefix, '', $last) : 0;

        return $prefix.str_pad((string) ($seq + 1), $length, '0', STR_PAD_LEFT);
    }

    public function getTable(): string
    {
        return config('invoices.estimates_table', 'estimates');
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
        return $this->hasMany(EstimateLineItem::class)->orderBy('position');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(EstimateCostItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function recalculate(): self
    {
        $subtotal = (int) $this->lineItems()->sum('amount_cents');
        $this->subtotal_cents = $subtotal;
        $this->total_cents = $subtotal - $this->discount_cents + $this->tax_cents;

        return $this;
    }

    public function costTotalCents(): int
    {
        return (int) ($this->relationLoaded('costItems')
            ? $this->costItems->sum('amount_cents')
            : $this->costItems()->sum('amount_cents'));
    }

    public function marginCents(): int
    {
        return $this->total_cents - $this->costTotalCents();
    }

    public function convertToInvoice(array $overrides = []): Invoice
    {
        $invoice = DB::transaction(function () use ($overrides) {
            $invoice = Invoice::create(array_merge([
                'owner_type' => $this->owner_type,
                'owner_id' => $this->owner_id,
                'customer_id' => $this->customer_id,
                'status' => InvoiceStatus::Draft,
                'issue_date' => now()->toDateString(),
                'currency' => $this->currency,
                'discount_cents' => $this->discount_cents,
                'tax_cents' => $this->tax_cents,
                'notes' => $this->notes,
                'terms' => $this->terms,
            ], $overrides));

            foreach ($this->lineItems as $item) {
                $invoice->lineItems()->create([
                    'invoiceable_type' => $item->estimateable_type,
                    'invoiceable_id' => $item->estimateable_id,
                    'position' => $item->position,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price_cents' => $item->unit_price_cents,
                    'metadata' => $item->metadata,
                ]);
            }

            $invoice->recalculate()->save();

            $this->forceFill([
                'status' => EstimateStatus::Accepted,
                'accepted_at' => now()->toDateString(),
                'converted_invoice_id' => $invoice->id,
            ])->save();

            return $invoice;
        });

        EstimateAccepted::dispatch($this, $invoice);

        return $invoice;
    }

    protected static function newFactory(): EstimateFactory
    {
        return EstimateFactory::new();
    }
}
