<?php

namespace Whilesmart\Invoices\Traits;

trait IsInvoiceable
{
    public function toLineItemAttributes(): array
    {
        return [
            'description' => $this->name ?? $this->title ?? (string) $this->getKey(),
            'quantity' => 1,
            'unit' => $this->default_unit ?? null,
            'unit_price_cents' => (int) ($this->default_price_cents ?? 0),
            'metadata' => null,
        ];
    }
}
