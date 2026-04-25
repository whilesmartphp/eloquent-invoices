<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Invoices\Models\Invoice;

class HostWorkspace extends Model
{
    protected $table = 'workspaces';

    protected $guarded = [];

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'owner');
    }
}
