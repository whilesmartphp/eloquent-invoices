<?php

namespace Whilesmart\Invoices\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Invoices\Models\Invoice;

trait HasInvoices
{
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'owner');
    }
}
