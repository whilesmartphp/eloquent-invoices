<?php

namespace Whilesmart\Invoices\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Invoices\Enums\InvoiceStatus;
use Whilesmart\Invoices\Models\Invoice;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issue = $this->faker->dateTimeBetween('-60 days', 'now');

        return [
            'number' => config('invoices.number_prefix', 'INV-').$this->faker->unique()->numerify('######'),
            'status' => InvoiceStatus::Draft,
            'issue_date' => $issue,
            'due_date' => (clone $issue)->modify('+30 days'),
            'currency' => 'USD',
            'subtotal_cents' => 0,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'amount_paid_cents' => 0,
        ];
    }
}
