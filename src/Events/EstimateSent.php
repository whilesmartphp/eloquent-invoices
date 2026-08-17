<?php

namespace Whilesmart\Invoices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Whilesmart\Invoices\Models\Estimate;

class EstimateSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Estimate $estimate,
    ) {}
}
