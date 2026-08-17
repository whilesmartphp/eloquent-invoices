<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\Invoices\Models\Estimate;
use Whilesmart\Invoices\Models\Invoice;

class EstimateApiTest extends TestCase
{
    #[Test]
    public function post_creates_an_estimate_with_line_and_cost_items(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);

        $this->postJson('/api/estimates', [
            'owner_type' => HostWorkspace::class,
            'owner_id' => $ws->id,
            'issue_date' => '2026-08-17',
            'currency' => 'USD',
            'tax_cents' => 500,
            'line_items' => [
                ['description' => 'Design', 'quantity' => 2, 'unit_price_cents' => 10000],
                ['description' => 'Build', 'quantity' => 1, 'unit_price_cents' => 30000],
            ],
            'cost_items' => [
                ['description' => 'Labour', 'quantity' => 10, 'unit_cost_cents' => 2000],
            ],
        ])->assertStatus(201)
            ->assertJsonPath('data.number', 'EST-00001')
            ->assertJsonPath('data.total_cents', 50500)
            ->assertJsonPath('data.cost_total_cents', 20000)
            ->assertJsonPath('data.margin_cents', 30500);

        $this->assertSame(1, Estimate::count());
    }

    #[Test]
    public function accept_converts_the_estimate_to_a_draft_invoice(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $estimate = $ws->estimates()->create(['issue_date' => '2026-08-17', 'currency' => 'USD']);
        $estimate->lineItems()->create(['description' => 'Work', 'quantity' => 1, 'unit_price_cents' => 40000, 'position' => 0]);
        $estimate->recalculate()->save();

        $this->postJson("/api/estimates/{$estimate->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.estimate.status', 'accepted')
            ->assertJsonPath('data.invoice.status', 'draft')
            ->assertJsonPath('data.invoice.total_cents', 40000);

        $this->assertSame(1, Invoice::count());
    }
}
