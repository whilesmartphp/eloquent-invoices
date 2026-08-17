<?php

namespace Tests\Feature;

use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\Invoices\Enums\EstimateStatus;
use Whilesmart\Invoices\Enums\InvoiceStatus;
use Whilesmart\Invoices\Models\Estimate;
use Whilesmart\Invoices\Models\Invoice;

class EstimateTest extends TestCase
{
    private function estimateWithItems(HostWorkspace $ws): Estimate
    {
        $estimate = $ws->estimates()->create([
            'issue_date' => now()->toDateString(),
            'currency' => 'USD',
            'discount_cents' => 200,
            'tax_cents' => 500,
        ]);

        $estimate->lineItems()->create(['description' => 'Design', 'quantity' => 2, 'unit_price_cents' => 10000, 'position' => 0]);
        $estimate->lineItems()->create(['description' => 'Build', 'quantity' => 1, 'unit_price_cents' => 30000, 'position' => 1]);

        return $estimate;
    }

    public function test_it_numbers_and_recalculates_totals(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $estimate = $this->estimateWithItems($ws);

        $this->assertSame('EST-00001', $estimate->number);

        $estimate->recalculate()->save();

        // subtotal 50000 = 2*10000 + 30000; total 50300 = 50000 - 200 + 500
        $this->assertSame(50000, $estimate->subtotal_cents);
        $this->assertSame(50300, $estimate->total_cents);
    }

    public function test_cost_breakdown_feeds_margin_not_the_total(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $estimate = $this->estimateWithItems($ws);
        $estimate->recalculate()->save();

        $estimate->costItems()->create(['description' => 'Labour', 'quantity' => 10, 'unit_cost_cents' => 2000]);
        $estimate->costItems()->create(['description' => 'Materials', 'quantity' => 1, 'unit_cost_cents' => 5000]);

        $this->assertSame(25000, $estimate->costTotalCents());
        $this->assertSame(25300, $estimate->fresh()->marginCents());
        $this->assertSame(50300, $estimate->fresh()->total_cents);
    }

    public function test_accepting_converts_to_a_draft_invoice_carrying_line_items(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $estimate = $this->estimateWithItems($ws);
        $estimate->recalculate()->save();

        $invoice = $estimate->convertToInvoice();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(2, $invoice->lineItems()->count());
        $this->assertSame(50000, $invoice->subtotal_cents);
        $this->assertSame(50300, $invoice->total_cents);

        $estimate->refresh();
        $this->assertSame(EstimateStatus::Accepted, $estimate->status);
        $this->assertSame($invoice->id, $estimate->converted_invoice_id);
    }
}
