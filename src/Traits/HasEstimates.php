<?php

namespace Whilesmart\Invoices\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Invoices\Models\Estimate;

trait HasEstimates
{
    public function estimates(): MorphMany
    {
        return $this->morphMany(Estimate::class, 'owner');
    }
}
