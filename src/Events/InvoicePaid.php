<?php

namespace Whilesmart\Invoices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Invoices\Models\Invoice;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Invoice $invoice, public int $amountCents) {}
}
