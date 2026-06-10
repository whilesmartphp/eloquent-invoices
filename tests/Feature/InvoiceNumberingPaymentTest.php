<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\Invoices\Events\InvoicePaid;
use Whilesmart\Invoices\Events\InvoicePartiallyPaid;

class InvoiceNumberingPaymentTest extends TestCase
{
    private function draft(HostWorkspace $ws, array $overrides = [])
    {
        return $ws->invoices()->create(array_merge([
            'status' => 'draft',
            'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ], $overrides));
    }

    #[Test]
    public function next_number_follows_the_highest_existing_for_the_owner(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $this->draft($ws, ['number' => 'INV-00041']);

        $next = $this->draft($ws);

        $this->assertSame('INV-00042', $next->fresh()->number);
    }

    #[Test]
    public function number_padding_length_is_configurable(): void
    {
        config()->set('invoices.number_length', 8);
        $ws = HostWorkspace::create(['name' => 'Acme']);

        $invoice = $this->draft($ws);

        $this->assertSame('INV-00000001', $invoice->fresh()->number);
    }

    #[Test]
    public function paying_in_full_dispatches_invoice_paid_only(): void
    {
        Event::fake([InvoicePaid::class, InvoicePartiallyPaid::class]);
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $this->draft($ws, ['status' => 'sent', 'total_cents' => 1000]);

        $this->postJson("/api/invoices/{$invoice->id}/mark-paid", ['amount_cents' => 1000])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        Event::assertDispatched(InvoicePaid::class);
        Event::assertNotDispatched(InvoicePartiallyPaid::class);
        $this->assertDatabaseHas('payments', [
            'payable_id' => $invoice->id,
            'amount_cents' => 1000,
            'status' => 'succeeded',
        ]);
    }

    #[Test]
    public function a_partial_payment_dispatches_invoice_partially_paid_only(): void
    {
        Event::fake([InvoicePaid::class, InvoicePartiallyPaid::class]);
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $this->draft($ws, ['status' => 'sent', 'total_cents' => 1000]);

        $this->postJson("/api/invoices/{$invoice->id}/mark-paid", ['amount_cents' => 400])
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_paid');

        Event::assertDispatched(InvoicePartiallyPaid::class);
        Event::assertNotDispatched(InvoicePaid::class);
    }
}
