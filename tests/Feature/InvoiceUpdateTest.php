<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;

class InvoiceUpdateTest extends TestCase
{
    #[Test]
    public function put_replaces_line_items_and_recalculates_total(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);
        $invoice->lineItems()->create(['description' => 'Old', 'quantity' => 1, 'unit_price_cents' => 1000, 'position' => 0]);
        $invoice->recalculate()->save();

        $this->putJson("/api/invoices/{$invoice->id}", [
            'notes' => 'Updated terms',
            'line_items' => [
                ['description' => 'New A', 'quantity' => 2, 'unit_price_cents' => 5000],
                ['description' => 'New B', 'quantity' => 1, 'unit_price_cents' => 2000],
            ],
        ])->assertStatus(200);

        $fresh = $invoice->fresh();
        $this->assertSame(2, $fresh->lineItems()->count());
        $this->assertSame(12000, $fresh->total_cents);
        $this->assertSame('Updated terms', $fresh->notes);
    }

    #[Test]
    public function put_can_update_header_without_touching_line_items(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);
        $invoice->lineItems()->create(['description' => 'Keep', 'quantity' => 1, 'unit_price_cents' => 3000, 'position' => 0]);
        $invoice->recalculate()->save();

        $this->putJson("/api/invoices/{$invoice->id}", ['notes' => 'Just a note'])
            ->assertStatus(200);

        $fresh = $invoice->fresh();
        $this->assertSame(1, $fresh->lineItems()->count());
        $this->assertSame(3000, $fresh->total_cents);
    }

    #[Test]
    public function put_rejects_editing_a_void_invoice(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'void', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", ['notes' => 'nope'])
            ->assertStatus(422);
    }

    #[Test]
    public function put_rejects_editing_a_paid_invoice(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'paid', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", ['notes' => 'nope'])
            ->assertStatus(422);
    }
}
