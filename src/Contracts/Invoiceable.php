<?php

namespace Whilesmart\Invoices\Contracts;

interface Invoiceable
{
    /**
     * Snapshot attributes that get copied into a fresh invoice line item.
     * Returned keys: description, quantity, unit, unit_price_cents, metadata.
     */
    public function toLineItemAttributes(): array;
}
