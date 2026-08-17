<?php

namespace Whilesmart\Invoices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Invoices\Models\Estimate;
use Whilesmart\Invoices\Models\Invoice;

class EstimateAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Estimate $estimate,
        public Invoice $invoice,
    ) {}
}
