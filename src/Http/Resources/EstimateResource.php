<?php

namespace Whilesmart\Invoices\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EstimateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'customer_id' => $this->customer_id,
            'number' => $this->number,
            'status' => $this->status?->value,
            'issue_date' => $this->issue_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'sent_at' => $this->sent_at?->toDateString(),
            'accepted_at' => $this->accepted_at?->toDateString(),
            'currency' => $this->currency,
            'subtotal_cents' => (int) $this->subtotal_cents,
            'discount_cents' => (int) $this->discount_cents,
            'tax_cents' => (int) $this->tax_cents,
            'total_cents' => (int) $this->total_cents,
            'cost_total_cents' => $this->costTotalCents(),
            'margin_cents' => $this->marginCents(),
            'converted_invoice_id' => $this->converted_invoice_id,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'metadata' => $this->metadata,
            'line_items' => $this->whenLoaded('lineItems', fn () => $this->lineItems->map(fn ($item) => [
                'id' => $item->id,
                'estimateable_type' => $item->estimateable_type,
                'estimateable_id' => $item->estimateable_id,
                'position' => $item->position,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unit_price_cents' => (int) $item->unit_price_cents,
                'amount_cents' => (int) $item->amount_cents,
                'metadata' => $item->metadata,
            ])),
            'cost_items' => $this->whenLoaded('costItems', fn () => $this->costItems->map(fn ($item) => [
                'id' => $item->id,
                'estimate_line_item_id' => $item->estimate_line_item_id,
                'category' => $item->category,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_cost_cents' => (int) $item->unit_cost_cents,
                'amount_cents' => (int) $item->amount_cents,
                'notes' => $item->notes,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
