<?php

namespace Whilesmart\Invoices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Whilesmart\Customers\Http\Resources\CustomerResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'number' => $this->number,
            'status' => $this->status?->value,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'sent_at' => $this->sent_at?->toDateString(),
            'paid_at' => $this->paid_at?->toDateString(),
            'currency' => $this->currency,
            'subtotal_cents' => $this->subtotal_cents,
            'discount_cents' => $this->discount_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'amount_paid_cents' => $this->amount_paid_cents,
            'balance_cents' => $this->balanceCents(),
            'notes' => $this->notes,
            'terms' => $this->terms,
            'metadata' => $this->metadata,
            'line_items' => $this->whenLoaded('lineItems', fn () => $this->lineItems->map(fn ($item) => [
                'id' => $item->id,
                'position' => $item->position,
                'invoiceable_type' => $item->invoiceable_type,
                'invoiceable_id' => $item->invoiceable_id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unit_price_cents' => $item->unit_price_cents,
                'amount_cents' => $item->amount_cents,
                'metadata' => $item->metadata,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
