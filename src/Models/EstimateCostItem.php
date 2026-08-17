<?php

namespace Whilesmart\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateCostItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('invoices.estimate_cost_items_table', 'estimate_cost_items');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(EstimateLineItem::class, 'estimate_line_item_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->amount_cents = (int) round($item->quantity * $item->unit_cost_cents);
        });
    }
}
