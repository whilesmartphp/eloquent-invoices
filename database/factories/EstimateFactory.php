<?php

namespace Whilesmart\Invoices\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Invoices\Enums\EstimateStatus;
use Whilesmart\Invoices\Models\Estimate;

class EstimateFactory extends Factory
{
    protected $model = Estimate::class;

    public function definition(): array
    {
        return [
            'status' => EstimateStatus::Draft->value,
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
        ];
    }
}
