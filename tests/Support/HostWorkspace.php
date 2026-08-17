<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Invoices\Models\Invoice;
use Whilesmart\Invoices\Traits\HasEstimates;

class HostWorkspace extends Model
{
    use HasEstimates;

    protected $table = 'workspaces';

    protected $guarded = [];

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'owner');
    }
}
