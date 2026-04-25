<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\Invoices\Models\Invoice;

class InvoiceApiTest extends TestCase
{
    #[Test]
    public function post_creates_an_invoice(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);

        $response = $this->postJson('/api/invoices', [
            'owner_type' => HostWorkspace::class,
            'owner_id' => $ws->id,
            'number' => 'INV-1',
            'issue_date' => '2026-04-25',
            'currency' => 'USD',
            'line_items' => [
                ['description' => 'Consulting', 'quantity' => 2, 'unit_price_cents' => 5000],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.number', 'INV-1');
        $this->assertSame(1, Invoice::count());
        $this->assertSame(10000, Invoice::first()->total_cents);
    }

    #[Test]
    public function index_filters_by_owner(): void
    {
        $a = HostWorkspace::create(['name' => 'A']);
        $b = HostWorkspace::create(['name' => 'B']);

        $a->invoices()->create([
            'number' => 'INV-A1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);
        $b->invoices()->create([
            'number' => 'INV-B1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $response = $this->getJson('/api/invoices?owner_type='.urlencode(HostWorkspace::class)."&owner_id={$a->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.meta.total', 1);
    }

    #[Test]
    public function send_marks_an_invoice_as_sent(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->postJson("/api/invoices/{$invoice->id}/send")->assertStatus(200);

        $this->assertSame('sent', $invoice->fresh()->status->value);
        $this->assertNotNull($invoice->fresh()->sent_at);
    }
}
